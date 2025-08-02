-- BizHawk Lua Script for Contra Final Score Capture
-- Monitors Player 1's lives and captures final score when lives reach zero
-- Saves high scores to JSON Lines format with player initials
-- Author: Jeremy Stevens
-- Date: July 29, 2025

-- Memory addresses for Contra (NES) - From official RAM map
local PLAYER1_LIVES_ADDR = 0x0032       -- P1 Number Lives (0x00 is last life)
local PLAYER1_GAME_OVER_ADDR = 0x0038   -- P1 Game Over Status (0x00 not game over, 0x01 game over)

-- Player 1 Score addresses: 0x07E2–0x07E3 (2 bytes, little-endian, raw binary ×100)
local PLAYER1_SCORE_ADDR_LOW = 0x07E2   -- Low byte (little-endian)
local PLAYER1_SCORE_ADDR_HIGH = 0x07E3  -- High byte (little-endian)

-- State tracking variables
local previous_lives = -1
local score_captured = false
local last_capture_frame = -1000  -- Initialize to allow first capture
local capture_cooldown = 120  -- 2 seconds at 60 FPS to prevent duplicates
local game_over_frame = 0     -- Frame when game over was first detected
local capture_delay = 10      -- Frames to wait after game over before capturing
local cheats_detected = false
local awaiting_initials = false  -- Flag to indicate we're waiting for player initials
local player_initials = ""       -- Store the entered initials
local initials_input = ""        -- Current input buffer
local input_form = nil           -- Form for initials input
local game_over_detected = false -- Flag to track game over state
local previous_keys = {}         -- Track previous frame's key states

-- Script timing
local script_start_time = os.time()

-- Custom JSON encoder
local function json_encode_string(str)
    -- Escape special characters in JSON strings
    str = string.gsub(str, "\\", "\\\\")
    str = string.gsub(str, '"', '\\"')
    str = string.gsub(str, "\n", "\\n")
    str = string.gsub(str, "\r", "\\r")
    str = string.gsub(str, "\t", "\\t")
    return '"' .. str .. '"'
end

local function json_encode_value(value)
    local value_type = type(value)
    
    if value_type == "string" then
        return json_encode_string(value)
    elseif value_type == "number" then
        return tostring(value)
    elseif value_type == "boolean" then
        return value and "true" or "false"
    elseif value_type == "nil" then
        return "null"
    elseif value_type == "table" then
        -- Simple table encoder (assumes string keys for objects)
        local parts = {}
        local is_array = true
        local max_index = 0
        
        -- Check if it's an array or object
        for k, v in pairs(value) do
            if type(k) ~= "number" then
                is_array = false
                break
            else
                max_index = math.max(max_index, k)
            end
        end
        
        if is_array then
            -- Encode as JSON array
            for i = 1, max_index do
                table.insert(parts, json_encode_value(value[i]))
            end
            return "[" .. table.concat(parts, ",") .. "]"
        else
            -- Encode as JSON object
            for k, v in pairs(value) do
                local key_str = json_encode_string(tostring(k))
                local val_str = json_encode_value(v)
                table.insert(parts, key_str .. ":" .. val_str)
            end
            return "{" .. table.concat(parts, ",") .. "}"
        end
    else
        return "null"
    end
end

-- Generate ISO 8601 timestamp
local function get_iso_timestamp()
    local time_table = os.date("*t")
    return string.format("%04d-%02d-%02dT%02d:%02d:%02d.000000Z",
        time_table.year, time_table.month, time_table.day,
        time_table.hour, time_table.min, time_table.sec)
end

-- Initialize logging
local function init_logging()
    local timestamp = os.date("%Y%m%d_%H%M%S")
    local filename = string.format("contra_scores_%s.txt", timestamp)
    log_file = io.open(filename, "w")
    if log_file then
        log_file:write(string.format("Contra Score Capture Log - Started: %s\n", os.date("%Y-%m-%d %H:%M:%S")))
        log_file:write("=" .. string.rep("=", 50) .. "\n")
        log_file:flush()
        console.log("Score logging initialized: " .. filename)
    else
        console.log("Warning: Could not create log file")
    end
end

-- Save high score to JSON Lines file
local function save_highscore_json(initials, score)
    local highscore_data = {
        game = "Contra (NES)",
        initials = initials,
        score = score,
        timestamp = get_iso_timestamp()
    }
    
    local json_string = json_encode_value(highscore_data)
    
    -- Append to highscores.jsonl file
    local jsonl_file = io.open("highscores.jsonl", "a")
    if jsonl_file then
        jsonl_file:write(json_string .. "\n")
        jsonl_file:close()
        console.log("High score saved to highscores.jsonl")
    else
        console.log("Error: Could not save to highscores.jsonl")
    end
end

-- Send score to API endpoint (BizHawk compatible)
local function send_score_to_api(initials, score)
    local payload = json_encode_value({
        game = "Contra (NES)",
        initials = initials,
        score = score,
        timestamp = get_iso_timestamp()
    })
    
    -- Check if comm functions are available
    if not comm or not comm.httpPost then
        console.log("❌ HTTP POST not available")
        return
    end
    
    console.log("🚀 Sending score to API...")
    console.log("Payload: " .. payload)
    
    -- Try the POST request
    local success, response = pcall(comm.httpPost, "http://localhost/api/submit_score.php", payload)
    
    if success and response and response ~= "" then
        console.log("✅ Score successfully sent to API!")
        console.log("API Response: " .. tostring(response))
    elseif success then
        console.log("✅ Score sent to API (empty response)")
    else
        console.log("❌ Failed to send score to API")
        console.log("Error: " .. tostring(response))
        
        -- Try a simple test to see if HTTP is working at all
        console.log("Testing basic HTTP connectivity...")
        local test_success, test_response = pcall(comm.httpGet, "http://www.google.com")
        if test_success and test_response then
            console.log("✅ Basic HTTP is working (can reach external sites)")
            console.log("❌ Your localhost API might not be running")
        else
            console.log("❌ HTTP is not working at all in BizHawk")
        end
    end
end

-- Read Player 1's current lives
local function read_player1_lives()
    return memory.read_u8(PLAYER1_LIVES_ADDR)
end

-- Read Player 1's game over status
local function read_player1_game_over_status()
    return memory.read_u8(PLAYER1_GAME_OVER_ADDR)
end

-- Read Player 1's current score (2 bytes, little-endian, raw binary ×100)
local function read_player1_score()
    local low = memory.read_u8(PLAYER1_SCORE_ADDR_LOW)   -- Low byte
    local high = memory.read_u8(PLAYER1_SCORE_ADDR_HIGH) -- High byte
    
    -- Combine little-endian 16-bit value and multiply by 100 for display score
    local raw_score = low + (high * 256)  -- Little-endian: low byte + (high byte << 8)
    local display_score = raw_score * 100
    
    return display_score
end

-- Check for Game Genie cheat codes based on specific memory addresses
local function detect_cheats()
    -- Game Genie cheat addresses and their activated values
    local genie_cheats = {
        -- Lives cheats
        {addr = 0xDA03, value = 0xB5, name = "Infinite Lives"},
        {addr = 0xDA03, value = 0xA5, name = "Infinite Lives (Alt)"},
        {addr = 0xDA03, value = 0xF7, name = "Extra Life on Death"},
        {addr = 0xC468, value = 0x63, name = "30 Lives"},
        {addr = 0xC462, value = 0x1D, name = "P1+P2 30 Lives"},
        {addr = 0xC462, value = 0x9E, name = "175 Lives"},
        
        -- Weapon cheats
        {addr = 0xDAD3, value = 0x2C, name = "Keep Weapon"},
        {addr = 0xDAD2, value = 0x01, name = "Start with Machine Gun"},
        {addr = 0xDAD2, value = 0x02, name = "Start with Flame Thrower"},
        {addr = 0xDAD2, value = 0x03, name = "Start with Spread Gun"},
        {addr = 0xDAD2, value = 0x04, name = "Start with Laser"},
        
        -- Invincibility cheats
        {addr = 0xD467, value = 0xB5, name = "Invincibility"},
        {addr = 0xE2C9, value = 0xAD, name = "Alternate Invincible"},
        {addr = 0xD53D, value = 0xB0, name = "Super Body"},
        
        -- Movement cheats
        {addr = 0xD6E9, value = 0xFA, name = "Jump Higher"},
        {addr = 0xD9F0, value = 0x14, name = "Jump Higher 2"},
        {addr = 0xD476, value = 0x20, name = "Jump Mid Air 1"},
        {addr = 0xD477, value = 0x9F, name = "Jump Mid Air 2"},
        {addr = 0xD478, value = 0xD6, name = "Jump Mid Air 3"},
        {addr = 0xD479, value = 0xEA, name = "Jump Mid Air 4"},
        {addr = 0xD480, value = 0xB5, name = "Jump Mid Air 5"},
        {addr = 0xD9E9, value = 0xB5, name = "Jump Mid Air 6"},
        {addr = 0xD54A, value = 0x60, name = "Jump Mid Air 7"},
        {addr = 0xD75D, value = 0x04, name = "Run 4x Faster 1"},
        {addr = 0xD761, value = 0xFC, name = "Run 4x Faster 2"},
        {addr = 0xE01D, value = 0xB5, name = "Walk on Water"},
        
        -- Level/Game cheats
        {addr = 0xC479, value = 0x30, name = "Level Select"},
        {addr = 0xD04D, value = 0x3B, name = "Press Start to Complete Level"},
        {addr = 0xCD5A, value = 0x30, name = "Turn Off Electric Barrier"},
        
        -- Combat cheats
        {addr = 0xD063, value = 0x98, name = "Harder Boss"},
        {addr = 0xE3F8, value = 0x94, name = "Bullets Through Enemies"},
        {addr = 0xE358, value = 0xA9, name = "Enemies Die Auto 1"},
        {addr = 0xE360, value = 0x42, name = "Enemies Die Auto 2"},
        {addr = 0xC4A7, value = 0xAD, name = "Tons of Points"},
        
        -- Visual/Audio cheats
        {addr = 0x8348, value = 0xD9, name = "Slow Weapons Capsules"},
        {addr = 0xC7C9, value = 0x98, name = "Walk on Exploded Bridge"},
        {addr = 0x88C4, value = 0xBD, name = "DPCM Pop Reducer 1"},
        {addr = 0xC07E, value = 0xA9, name = "Black and White Mode 1"},
        {addr = 0xC07F, value = 0x1F, name = "Black and White Mode 2"},
        {addr = 0xCEE0, value = 0x14, name = "Remove Lifebar Indicators"}
    }
    
    -- Check each Game Genie cheat address
    for i = 1, #genie_cheats do
        local cheat = genie_cheats[i]
        local memory_value = memory.read_u8(cheat.addr)
        
        if memory_value == cheat.value then
            return true, cheat.name .. " Code"
        end
    end
    
    -- Check for BizHawk cheat database active (but only if function exists)
    if client and client.getcheats then
        local success, cheats = pcall(client.getcheats)
        if success and cheats and #cheats > 0 then
            -- Check if any cheats are actually enabled, not just present
            for i = 1, #cheats do
                if cheats[i].enabled then
                    return true, "Emulator Cheat Active"
                end
            end
        end
    end
    
    -- Check for obvious infinite lives value (255)
    local lives = read_player1_lives()
    if lives == 255 then
        return true, "Infinite Lives (Memory)"
    end
    
    return false, ""
end

-- Format score without unnecessary leading zeros
local function format_score(score)
    -- Add commas for thousands separators
    local formatted = tostring(score)
    local result = ""
    local len = string.len(formatted)
    
    for i = 1, len do
        local digit = string.sub(formatted, i, i)
        result = result .. digit
        
        -- Add comma if there are more digits and position is right for comma
        local remaining = len - i
        if remaining > 0 and remaining % 3 == 0 then
            result = result .. ","
        end
    end
    
    return result
end

-- Handle keyboard input for initials
local function handle_initials_input()
    local keys = input.get()
    
    -- Handle letter keys A-Z
    for key, pressed in pairs(keys) do
        local was_pressed_last_frame = previous_keys[key] or false
        local just_pressed = pressed and not was_pressed_last_frame
        
        if just_pressed then
            -- Convert key names to letters (assuming standard key names)
            local letter = nil
            if string.len(key) == 1 and string.match(key, "[A-Za-z]") then
                letter = string.upper(key)
            elseif key == "Space" then
                letter = " "
            elseif key == "Backspace" and string.len(initials_input) > 0 then
                initials_input = string.sub(initials_input, 1, -2)
            elseif (key == "Return" or key == "Enter" or key == "NumpadEnter" or key == "KeypadEnter") and string.len(initials_input) >= 1 then
                -- Finalize initials entry
                player_initials = string.sub(initials_input .. "   ", 1, 3) -- Pad to 3 chars
                awaiting_initials = false
                previous_keys = {}  -- Reset key tracking
                return true
            end
            
            -- Add letter if we have room
            if letter and string.len(initials_input) < 3 then
                initials_input = initials_input .. letter
            end
        end
    end
    
    -- Store current key states for next frame
    previous_keys = {}
    for key, pressed in pairs(keys) do
        previous_keys[key] = pressed
    end
    
    return false
end

-- Log captured score with player initials
local function log_score(score)
    local timestamp = os.date("%Y-%m-%d %H:%M:%S")
    local formatted_score = format_score(score)
    
    -- Get player initials via keyboard input
    if not awaiting_initials then
        awaiting_initials = true
        initials_input = ""
        console.log("Enter your initials (A-Z, max 3 characters, press Enter when done)")
    end
    
    -- Handle input until initials are entered
    if awaiting_initials then
        local input_complete = handle_initials_input()
        if not input_complete then
            return -- Keep waiting for input
        end
    end
    
    -- Console output
    console.log("=" .. string.rep("=", 50))
    console.log("GAME OVER - SCORE CAPTURED!")
    console.log("Player: " .. player_initials)
    console.log("Final Score: " .. formatted_score)
    console.log("Time: " .. timestamp)
    console.log("=" .. string.rep("=", 50))
    
    -- Save to JSON Lines file
    save_highscore_json(player_initials, score)
    
    -- Send score to API
    send_score_to_api(player_initials, score)
    
    -- Reset for next game
    player_initials = ""
    initials_input = ""
    previous_keys = {}
end

-- Check if we're in a valid game state
local function is_valid_game_state()
    -- Basic validation - make sure we're not in menu or other non-game states
    local lives = read_player1_lives()
    return lives >= 0 and lives <= 10  -- Reasonable range for lives
end

-- Main monitoring function
local function monitor_game_state()
    if not is_valid_game_state() then
        return
    end
    
    -- Check for cheats first
    local cheat_found, cheat_type = detect_cheats()
    if cheat_found and not cheats_detected then
        cheats_detected = true
        console.log("CHEATS DETECTED: " .. cheat_type .. " - Score tracking disabled")
    elseif not cheat_found and cheats_detected then
        cheats_detected = false
        console.log("Cheats no longer detected - Score tracking re-enabled")
    end
    
    local current_lives = read_player1_lives()
    local current_frame = emu.framecount()
    
    -- Check if we need to reset capture state (new game started)
    if current_lives > 0 and score_captured then
        score_captured = false
        game_over_frame = 0
        game_over_detected = false
        console.log("New game detected - ready to capture next final score")
    end
    
    local game_over_status = read_player1_game_over_status()
    
    -- Detect game over using both lives count and game over status flag
    if game_over_status == 1 and not score_captured then
        if not game_over_detected then
            game_over_detected = true
            game_over_frame = current_frame
            if not cheats_detected then
                console.log("Game over detected - waiting for score to stabilize...")
            else
                console.log("Game over detected - but cheats are active, score will not be tracked")
            end
        end
        
        -- Only capture score if no cheats detected
        if not cheats_detected then
            -- Wait for the score to stabilize, then capture
            if current_frame - game_over_frame >= capture_delay then
                if current_frame - last_capture_frame > capture_cooldown then
                    local final_score = read_player1_score()
                    
                    -- Validate score (basic sanity check)
                    if final_score >= 0 and final_score <= 99999999 then
                        log_score(final_score)
                        if not awaiting_initials then  -- Only mark as captured after initials are entered
                            score_captured = true
                            last_capture_frame = current_frame
                        end
                    else
                        console.log(string.format("Warning: Invalid score detected (%d), skipping capture", final_score))
                    end
                end
            end
        else
            -- Mark as captured to prevent repeated messages
            score_captured = true
        end
    end
    
    -- Update previous state
    previous_lives = current_lives
end

-- Display player's score or input prompt
local function display_score_only()
    if cheats_detected then
        gui.text(10, 10, "Cheats are enabled", "red", "black")
        gui.text(10, 25, "Score keeping is disabled", "orange", "black")
    elseif awaiting_initials then
        gui.text(10, 10, "Enter your initials:", "yellow", "black")
        gui.text(10, 25, initials_input .. "_", "white", "black")
        gui.text(10, 40, "Press Enter when done", "cyan", "black")
    else
        local score = read_player1_score()
        gui.text(10, 10, string.format("Score: %s", format_score(score)), "white", "black")
    end
end

-- Cleanup function
local function cleanup()
    console.log("Script ended")
end

-- Initialize script
local function init_script()
    console.clear()
    console.log("Contra Final Score Capture Script Started")
    console.log("Monitoring Player 1 for game over...")
    console.log("Memory addresses:")
    console.log(string.format("  Lives: 0x%04X", PLAYER1_LIVES_ADDR))
    console.log(string.format("  Score: 0x%04X-0x%04X (2 bytes little-endian × 100)", PLAYER1_SCORE_ADDR_LOW, PLAYER1_SCORE_ADDR_HIGH))
    console.log("High scores will be saved to: highscores.jsonl")
    
    -- Test HTTP functionality on startup
    if comm then
        console.log("✅ comm library available")
        
        -- Try to initialize HTTP by setting a timeout first
        if comm.httpSetTimeout then
            comm.httpSetTimeout(10000)  -- 10 seconds
            console.log("✅ HTTP timeout set to 10 seconds")
        end
        
        -- Test HTTP after setting timeout
        if comm.httpTest then
            local http_status = comm.httpTest()
            console.log("HTTP Status: " .. tostring(http_status))
        end
        
        -- Try a simple GET request to test connectivity
        if comm.httpGet then
            console.log("Testing HTTP GET to google.com...")
            local get_result = comm.httpGet("http://www.google.com")
            if get_result then
                console.log("✅ HTTP GET test successful (received " .. string.len(get_result) .. " characters)")
            else
                console.log("❌ HTTP GET test failed")
            end
        end
        
        if comm.httpPost then
            console.log("✅ HTTP POST function available")
        else
            console.log("❌ HTTP POST function not available")
        end
    else
        console.log("❌ comm library not available")
    end
    
    console.log("Ready to capture final scores!")
end

-- Module Interface Functions for External Execution

-- Initialize the game module (called by detect_game.lua)
function initGameModule()
    init_script()
    
    -- Register event handlers
    event.onexit(cleanup)
    
    console.log("🎮 Contra module initialized successfully")
    return true
end

-- Main game loop function (called by detect_game.lua)
function gameModuleLoop()
    -- Return false to exit back to standard detection
    -- Return true to continue running
    
    monitor_game_state()
    display_score_only()
    
    return true -- Continue running the custom loop
end

-- Standalone execution (when run directly, not from detect_game.lua)
if not _G.EXTERNAL_EXECUTION then
    console.log("Running Contra module in standalone mode")
    
    -- Register event handlers
    event.onexit(cleanup)
    
    -- Main execution
    init_script()
    
    -- Main loop - this function is called every frame
    while true do
        monitor_game_state()
        display_score_only()
        emu.frameadvance()
    end
else
    console.log("Contra module loaded for external execution")
end
