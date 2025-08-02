#!/usr/bin/env python3
"""
BizHawk High Score Tracker
A Python utility for managing BizHawk emulator files and tracking high scores.
Features: System tray, file watcher, API integration, and desktop notifications.
"""

import os
import sys
import json
import requests
import time
import threading
from pathlib import Path
from datetime import datetime

# System tray and notifications
try:
    import pystray
    from pystray import MenuItem as item
    from PIL import Image, ImageDraw
except ImportError:
    print("Missing required packages. Please install:")
    print("pip install pystray pillow")
    sys.exit(1)

# File watching
try:
    from watchdog.observers import Observer
    from watchdog.events import FileSystemEventHandler
except ImportError:
    print("Missing watchdog package. Please install:")
    print("pip install watchdog")
    sys.exit(1)

# Toast notifications
try:
    from plyer import notification
except ImportError:
    print("Missing plyer package. Please install:")
    print("pip install plyer")
    sys.exit(1)


class GameFileWatcher(FileSystemEventHandler):
    """File system event handler for watching game-related file changes."""

    def __init__(self, api_url, download_callback=None, score_callback=None):
        super().__init__()
        self.api_url = api_url
        self.download_callback = download_callback
        self.score_callback = score_callback
        self.last_modified = {}

    def on_modified(self, event):
        if event.is_directory:
            return

        # Handle current_game.txt file changes
        if event.src_path.endswith('current_game.txt'):
            # Debounce rapid file changes
            current_time = time.time()
            if event.src_path in self.last_modified:
                if current_time - self.last_modified[event.src_path] < 1:
                    return

            self.last_modified[event.src_path] = current_time

            print(f"🎮 Current game file changed: {event.src_path}")
            self.process_current_game(event.src_path)

        # Handle highscores.json changes
        elif event.src_path.endswith('highscores.json'):
            # Debounce rapid file changes
            current_time = time.time()
            if event.src_path in self.last_modified:
                if current_time - self.last_modified[event.src_path] < 1:
                    return

            self.last_modified[event.src_path] = current_time

            print(f"📊 High score file changed: {event.src_path}")
            self.process_high_score(event.src_path)

    def process_current_game(self, file_path):
        """Process the current_game file and download game module if needed."""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                game_name = f.read().strip()

            # Check for null, empty, or invalid game names
            if game_name and game_name.lower() not in ['null', 'none', '']:
                print(f"🎮 New game detected: {game_name}")
                
                # Show initial detection notification
                self.show_notification("Game Detected", f"Found: {game_name}\nChecking for game module...")
                
                # Call download callback if provided
                if self.download_callback:
                    print(f"📥 Checking for game module: {game_name}")
                    success = self.download_callback(game_name)
                    
                    if success:
                        self.show_notification("✅ Module Downloaded", 
                                             f"Game module ready for {game_name}")
                    else:
                        self.show_notification("⚠️ Using Default Detection", 
                                             f"No specific module found for {game_name}")
                else:
                    self.show_notification("⚠️ No Download Handler", 
                                         f"Cannot download module for {game_name}")
            else:
                print("📴 No ROM loaded or invalid game name")
                self.show_notification("📴 No Game", "No ROM currently loaded")

        except Exception as e:
            print(f"✗ Error processing current_game file: {e}")
            self.show_notification("❌ Error", f"Failed to process game file: {str(e)}")

    def process_high_score(self, file_path):
        """Process the high score file and submit to API."""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                score_data = json.load(f)

            print(f"🎮 New high score detected:")
            print(f"   Game: {score_data.get('game', 'Unknown')}")
            print(f"   Score: {score_data.get('score', 0)}")
            print(f"   Initials: {score_data.get('initials', 'N/A')}")
            print(f"   Time: {score_data.get('timestamp', 'Unknown')}")

            # Submit to API
            success = self.submit_to_api(score_data)

            if success:
                self.show_notification("High Score Submitted!", 
                                     f"{score_data.get('game', 'Game')}: {score_data.get('score', 0)} points")
            else:
                self.show_notification("Submission Failed", 
                                     "Could not submit high score to API")

            # Call callback if provided
            if self.score_callback:
                self.score_callback(score_data, success)

        except Exception as e:
            print(f"✗ Error processing high score file: {e}")
            self.show_notification("Error", f"Failed to process high score: {str(e)}")

    def process_high_score(self, file_path):
        """Process the high score file and submit to API."""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                score_data = json.load(f)

            print(f"🎮 New high score detected:")
            print(f"   Game: {score_data.get('game', 'Unknown')}")
            print(f"   Score: {score_data.get('score', 0)}")
            print(f"   Initials: {score_data.get('initials', 'N/A')}")
            print(f"   Time: {score_data.get('timestamp', 'Unknown')}")

            # Submit to API
            success = self.submit_to_api(score_data)

            if success:
                self.show_notification("High Score Submitted!", 
                                     f"{score_data.get('game', 'Game')}: {score_data.get('score', 0)} points")
            else:
                self.show_notification("Submission Failed", 
                                     "Could not submit high score to API")

            # Call callback if provided
            if self.callback:
                self.callback(score_data, success)

        except Exception as e:
            print(f"✗ Error processing high score file: {e}")
            self.show_notification("Error", f"Failed to process high score: {str(e)}")

    def submit_to_api(self, score_data):
        """Submit score data to the API."""
        try:
            print(f"🌐 Submitting to API: {self.api_url}")
            response = requests.post(self.api_url, json=score_data, timeout=10)
            response.raise_for_status()
            print("✓ Successfully submitted to API")
            return True
        except requests.RequestException as e:
            print(f"✗ API submission failed: {e}")
            return False

    def show_notification(self, title, message):
        """Show desktop notification."""
        try:
            notification.notify(
                title=title,
                message=message,
                app_name="BizHawk High Score Tracker",
                timeout=5
            )
        except Exception as e:
            print(f"Could not show notification: {e}")


class BizHawkTool:
    def __init__(self):
        """Initialize the BizHawk tool with the current working directory as root."""
        self.root_dir = Path.cwd()
        self.lua_nes_dir = self.root_dir / "Lua" / "NES"  # Files are in Lua\NES subdirectory
        self.games_dir = self.lua_nes_dir / "games"  # Game modules directory
        self.api_url = "http://localhost/api/submit_score.php"
        self.observer = None
        self.icon = None
        self.running = True

        # GitHub repository for game modules
        self.github_modules_base = "https://raw.githubusercontent.com/jeremystevens/modules/main"

        # Pastebin URLs for initialization scripts (non-game specific)
        self.pastebin_urls = {
            "detect_game": "https://pastebin.com/raw/ie96z6Ls",
            "base_config": "https://pastebin.com/raw/hFpK4WGx"
        }

    def create_tray_icon(self):
        """Create system tray icon."""
        # Create a simple icon image
        width = 64
        height = 64
        image = Image.new('RGB', (width, height), color='blue')
        draw = ImageDraw.Draw(image)

        # Draw a simple game controller icon
        draw.rectangle([10, 20, 54, 44], fill='white', outline='black')
        draw.ellipse([5, 15, 15, 25], fill='gray', outline='black')
        draw.ellipse([49, 15, 59, 25], fill='gray', outline='black')
        draw.text((20, 28), "HS", fill='black')

        return image

    def on_quit(self, icon, item):
        """Quit the application."""
        print("🛑 Shutting down BizHawk High Score Tracker...")
        self.running = False
        if self.observer:
            self.observer.stop()
            self.observer.join()
        icon.stop()

    def on_show_status(self, icon, item):
        """Show current status."""
        status_msg = f"Watching: {self.lua_nes_dir / 'highscores.json'}\nAPI: {self.api_url}"
        self.show_notification("BizHawk Tracker Status", status_msg)

    def on_open_folder(self, icon, item):
        """Open the lua/nes folder."""
        try:
            if sys.platform == "win32":
                os.startfile(str(self.lua_nes_dir))
            elif sys.platform == "darwin":
                os.system(f"open '{self.lua_nes_dir}'")
            else:
                os.system(f"xdg-open '{self.lua_nes_dir}'")
        except Exception as e:
            print(f"Could not open folder: {e}")

    def show_notification(self, title, message):
        """Show desktop notification."""
        try:
            notification.notify(
                title=title,
                message=message,
                app_name="BizHawk High Score Tracker",
                timeout=5
            )
        except Exception as e:
            print(f"Could not show notification: {e}")

    def start_file_watcher(self):
        """Start watching for current_game and highscores.json changes."""
        def score_callback(score_data, success):
            """Callback when a score is processed."""
            if success:
                print(f"✅ Score successfully submitted for {score_data.get('game', 'Unknown')}")
            else:
                print(f"❌ Failed to submit score for {score_data.get('game', 'Unknown')}")

        def download_callback(game_name):
            """Callback when a new game is detected."""
            return self.download_game_module(game_name)

        event_handler = GameFileWatcher(
            self.api_url, 
            download_callback=download_callback,
            score_callback=score_callback
        )
        self.observer = Observer()
        self.observer.schedule(event_handler, str(self.lua_nes_dir), recursive=False)
        self.observer.start()
        print(f"👁 Watching for changes in:")
        print(f"   • {self.lua_nes_dir / 'current_game.txt'}")
        print(f"   • {self.lua_nes_dir / 'highscores.json'}")



    def run_tray_application(self):
        """Run the system tray application."""
        print("🚀 Starting BizHawk High Score Tracker...")

        # Start file watcher
        self.start_file_watcher()

        # Create tray icon
        icon_image = self.create_tray_icon()
        menu = pystray.Menu(
            item('Status', self.on_show_status),
            item('Open Folder', self.on_open_folder),
            pystray.Menu.SEPARATOR,
            item('Quit', self.on_quit)
        )

        self.icon = pystray.Icon("BizHawk Tracker", icon_image, menu=menu)

        # Show startup notification
        self.show_notification("BizHawk Tracker Started", 
                             f"Watching for high scores...\nAPI: {self.api_url}")

        # Run the tray icon (this will block)
        print("✓ System tray icon started. Right-click for options.")
        print("✓ High score tracker is now running in the background.")
        print("  - Load a NES ROM in BizHawk")
        print("  - Play games and get high scores!")
        print("  - Scores will be automatically submitted to the API")

        self.icon.run()

    def ensure_directories(self):
        """Create necessary directories if they don't exist."""
        try:
            self.lua_nes_dir.mkdir(parents=True, exist_ok=True)
            self.games_dir.mkdir(parents=True, exist_ok=True)  # Create games directory
            print(f"✓ Ensured directories exist:")
            print(f"  Main: {self.lua_nes_dir}")
            print(f"  Games: {self.games_dir}")
            return True
        except Exception as e:
            print(f"✗ Error creating directories: {e}")
            return False

    def download_detect_game_script(self):
        """Download the detect_game.lua script from pastebin and save it to Lua/NES directory."""
        url = self.pastebin_urls["detect_game"]
        script_path = self.lua_nes_dir / "detect_game.lua"

        # Check if file already exists
        if script_path.exists():
            print(f"✓ detect_game.lua already exists at {script_path}")
            print("  Skipping download...")
            return True

        try:
            print("📥 Downloading main detect_game.lua script from Pastebin...")
            print(f"  URL: {url}")
            response = requests.get(url, timeout=30)
            response.raise_for_status()

            # Save the script to the root directory
            with open(script_path, 'w', encoding='utf-8') as f:
                f.write(response.text)

            print(f"✅ Successfully downloaded detect_game.lua")
            print(f"   Location: {script_path}")
            print(f"   Size: {len(response.text)} characters")
            return True

        except requests.RequestException as e:
            print(f"✗ Error downloading script: {e}")
            return False
        except IOError as e:
            print(f"✗ Error saving script: {e}")
            return False

    def configure_lua_autoload(self):
        """Configure BizHawk to automatically load the detect_game.lua script."""
        config_path = self.root_dir / "config.ini"
        full_bizhawk_path = str(self.root_dir).replace("\\", "/")
        script_path = f"{full_bizhawk_path}/lua/nes/detect_game.lua"

        try:
            # Check if config.ini exists
            if not config_path.exists():
                print("config.ini not found, downloading base configuration...")

                # Download the base config.ini
                config_url = "https://pastebin.com/raw/hFpK4WGx"
                response = requests.get(config_url, timeout=30)
                response.raise_for_status()

                with open(config_path, 'w', encoding='utf-8') as f:
                    f.write(response.text)

                print(f"✓ Downloaded base config.ini to {config_path}")

            # Read and parse the JSON config
            with open(config_path, 'r', encoding='utf-8') as f:
                config_data = json.load(f)

            # Check if our script is already in the RecentLua list
            if "RecentLua" in config_data:
                recent_list = config_data["RecentLua"].get("recentlist", [])
                if script_path in recent_list:
                    print(f"✓ Lua script already configured for autoload in {config_path}")
                    return True

            # Update RecentLua section
            if "RecentLua" not in config_data:
                config_data["RecentLua"] = {
                    "recentlist": [],
                    "MAX_RECENT_FILES": 8,
                    "AutoLoad": False,
                    "Frozen": False
                }

            # Add our script to the beginning of the recent list
            recent_list = config_data["RecentLua"]["recentlist"]
            if script_path not in recent_list:
                recent_list.insert(0, script_path)
                # Keep only the most recent files (up to MAX_RECENT_FILES)
                max_files = config_data["RecentLua"].get("MAX_RECENT_FILES", 8)
                config_data["RecentLua"]["recentlist"] = recent_list[:max_files]

            # Enable AutoLoad
            config_data["RecentLua"]["AutoLoad"] = True

            # Also update the LuaConsole settings to enable AutoLoad
            if "CommonToolSettings" not in config_data:
                config_data["CommonToolSettings"] = {}

            if "BizHawk.Client.EmuHawk.LuaConsole" not in config_data["CommonToolSettings"]:
                config_data["CommonToolSettings"]["BizHawk.Client.EmuHawk.LuaConsole"] = {
                    "_wndx": 78,
                    "_wndy": 78,
                    "Width": 600,
                    "Height": 386,
                    "SaveWindowPosition": True,
                    "TopMost": False,
                    "FloatingWindow": True,
                    "AutoLoad": True
                }
            else:
                config_data["CommonToolSettings"]["BizHawk.Client.EmuHawk.LuaConsole"]["AutoLoad"] = True

            # Write the updated config back
            with open(config_path, 'w', encoding='utf-8') as f:
                json.dump(config_data, f, indent=2)

            print(f"✓ Updated BizHawk config.ini to autoload detect_game.lua")
            print(f"  Config file: {config_path}")
            print(f"  Script path: {script_path}")
            print(f"  RecentLua AutoLoad: {config_data['RecentLua']['AutoLoad']}")
            print(f"  LuaConsole AutoLoad: {config_data['CommonToolSettings']['BizHawk.Client.EmuHawk.LuaConsole']['AutoLoad']}")
            return True

        except requests.RequestException as e:
            print(f"✗ Error downloading base config.ini: {e}")
            return False
        except json.JSONDecodeError as e:
            print(f"✗ Error parsing config.ini JSON: {e}")
            return False
        except Exception as e:
            print(f"⚠ Error configuring autoload: {e}")
            print("  You can manually add the script via Tools -> Lua Console -> Settings -> Autoload Script")
            return False

    def verify_bizhawk_directory(self):
        """Verify we're running from a BizHawk directory."""
        # Look for common BizHawk files/directories
        bizhawk_indicators = [
            "EmuHawk.exe",
            "dll",
            "Firmware",
            "Tools"
        ]

        found_indicators = []
        for indicator in bizhawk_indicators:
            if (self.root_dir / indicator).exists():
                found_indicators.append(indicator)

        if found_indicators:
            print(f"✓ BizHawk directory detected (found: {', '.join(found_indicators)})")
            return True
        else:
            print("⚠ Warning: This doesn't appear to be a BizHawk directory")
            print("   Expected to find files like EmuHawk.exe, dll/, Firmware/, etc.")
            return False

    def run_initial_setup(self):
        """Run the initial setup process."""
        print("=" * 60)
        print("BizHawk High Score Tracker - Initial Setup")
        print("=" * 60)

        # Verify BizHawk directory
        self.verify_bizhawk_directory()

        # Ensure directories exist
        if not self.ensure_directories():
            return False

        # Download the detect_game script
        if not self.download_detect_game_script():
            return False

        # Configure autoload
        if not self.configure_lua_autoload():
            return False

        # Create game mappings
        if not self.create_game_mappings():
            return False

        print("\n✓ Initial setup completed successfully!")
        print("\nSetup Summary:")
        print(f"  • detect_game.lua: {self.lua_nes_dir / 'detect_game.lua'}")
        print(f"  • game_mappings.json: {self.lua_nes_dir / 'game_mappings.json'}")
        print(f"  • games directory: {self.games_dir}")
        print(f"  • BizHawk config.ini: Updated with autoload settings")
        print(f"  • API endpoint: {self.api_url}")
        print(f"  • GitHub modules: {self.github_modules_base}")
        print("\nNext steps:")
        print("1. Start BizHawk (EmuHawk.exe)")
        print("2. Load a NES ROM")
        print("3. Open Tools -> Lua Console")
        print("4. The detect_game.lua script should automatically load!")
        print("5. Game modules will be auto-downloaded from GitHub as needed!")
        return True

    def download_game_module(self, game_name):
        """Download game-specific module from GitHub."""
        # Check for null, empty, or invalid game names
        if not game_name or game_name.lower() in ['null', 'none', '']:
            print(f"⚠ Skipping download for invalid game name: '{game_name}'")
            return False

        # Ensure games directory exists
        try:
            self.games_dir.mkdir(parents=True, exist_ok=True)
        except Exception as e:
            print(f"✗ Error creating games directory: {e}")
            return False

        # Clean game name for filename
        clean_name = game_name.lower().replace(" ", "_").replace(".", "").replace("-", "_")
        clean_name = ''.join(c for c in clean_name if c.isalnum() or c == '_')

        module_filename = f"{clean_name}.lua"
        module_path = self.games_dir / module_filename

        # Check if module already exists
        if module_path.exists():
            print(f"✓ Module already exists: {module_filename}")
            return True

        # Try to download from GitHub
        module_url = f"{self.github_modules_base}/{module_filename}"

        try:
            print(f"📥 Downloading game module: {module_filename}")
            print(f"  URL: {module_url}")

            response = requests.get(module_url, timeout=30)
            response.raise_for_status()

            # Save the module
            with open(module_path, 'w', encoding='utf-8') as f:
                f.write(response.text)

            print(f"✅ Successfully downloaded: {module_filename}")
            print(f"   Location: {module_path}")
            print(f"   Size: {len(response.text)} characters")
            
            # Add the downloaded module to config.ini
            self.add_module_to_config(module_path)
            return True

        except requests.RequestException as e:
            print(f"⚠ Module not found on GitHub: {module_filename}")
            print(f"  Error: {e}")
            print(f"  Game will use default detection logic")
            return False
        except Exception as e:
            print(f"✗ Error downloading module: {e}")
            return False

    def create_game_mappings(self):
        """Create game_mappings.json file with sample configurations."""
        mappings_path = self.lua_nes_dir / "game_mappings.json"

        if mappings_path.exists():
            print(f"✓ game_mappings.json already exists at {mappings_path}")
            return True

        # Sample game mappings
        sample_mappings = {
            "games": {
                "Super Mario Bros.": {
                    "system": "NES",
                    "ram_mappings": {
                        "score": "0x07DD",
                        "lives": "0x075A",
                        "coins": "0x075E",
                        "world": "0x075F",
                        "level": "0x0760"
                    }
                },
                "Donkey Kong": {
                    "system": "NES", 
                    "ram_mappings": {
                        "score": "0x6000",
                        "lives": "0x6005"
                    }
                },
                "Pac-Man": {
                    "system": "NES",
                    "ram_mappings": {
                        "score": "0x0043",
                        "lives": "0x0056"
                    }
                }
            }
        }

        try:
            with open(mappings_path, 'w', encoding='utf-8') as f:
                json.dump(sample_mappings, f, indent=2)

            print(f"✓ Created game_mappings.json with sample configurations")
            print(f"  Location: {mappings_path}")
            return True

        except Exception as e:
            print(f"✗ Error creating game_mappings.json: {e}")
            return False

    def add_module_to_config(self, module_path):
        """Add a downloaded game module to BizHawk's config.ini RecentLua list."""
        config_path = self.root_dir / "config.ini"
        # Convert to forward slashes and ensure lowercase lua/nes path for BizHawk config
        script_path = str(module_path).replace('\\', '/').replace('/Lua/NES/', '/lua/nes/').strip()

        try:
            # Check if config.ini exists
            if not config_path.exists():
                print("⚠ config.ini not found, cannot add module to autoload")
                return False

            # Read and parse the JSON config
            with open(config_path, 'r', encoding='utf-8') as f:
                config_data = json.load(f)

            # Check if our script is already in the RecentLua list
            if "RecentLua" in config_data:
                recent_list = config_data["RecentLua"].get("recentlist", [])
                if script_path in recent_list:
                    print(f"✓ Module already in config: {module_path.name}")
                    return True

            # Ensure RecentLua section exists
            if "RecentLua" not in config_data:
                config_data["RecentLua"] = {
                    "recentlist": [],
                    "MAX_RECENT_FILES": 8,
                    "AutoLoad": False,
                    "Frozen": False
                }

            # Add our module to the beginning of the recent list
            recent_list = config_data["RecentLua"]["recentlist"]
            if script_path not in recent_list:
                recent_list.insert(0, script_path)
                # Keep only the most recent files (up to MAX_RECENT_FILES)
                max_files = config_data["RecentLua"].get("MAX_RECENT_FILES", 8)
                config_data["RecentLua"]["recentlist"] = recent_list[:max_files]

            # Write the updated config back
            with open(config_path, 'w', encoding='utf-8') as f:
                json.dump(config_data, f, indent=2, ensure_ascii=False)

            print(f"✓ Added {module_path.name} to BizHawk RecentLua list")
            print(f"  Script path: {script_path}")
            return True

        except json.JSONDecodeError as e:
            print(f"✗ Error parsing config.ini JSON: {e}")
            return False
        except Exception as e:
            print(f"⚠ Error adding module to config: {e}")
            return False


def main():
    """Main entry point."""
    try:
        tool = BizHawkTool()

        # Check if this is first run (no setup completed yet)
        if not (tool.lua_nes_dir / "detect_game.lua").exists():
            print("🔧 First run detected - running initial setup...")
            success = tool.run_initial_setup()

            if not success:
                print("\n❌ Setup failed. Please check the errors above.")
                print("Make sure you're running this from your BizHawk directory.")
                input("Press Enter to exit...")
                sys.exit(1)

            print("\n🎉 Setup complete! Starting high score tracker...")
            time.sleep(2)  # Brief pause
        else:
            print("✓ Setup already completed. Starting high score tracker...")

        # Run as system tray application
        tool.run_tray_application()

    except KeyboardInterrupt:
        print("\n\n⏹ Application stopped by user.")
        sys.exit(0)
    except Exception as e:
        print(f"\n💥 Unexpected error: {e}")
        print("Please check that all required packages are installed:")
        print("pip install requests pystray pillow watchdog plyer")
        input("Press Enter to exit...")
        sys.exit(1)


if __name__ == "__main__":
    main()