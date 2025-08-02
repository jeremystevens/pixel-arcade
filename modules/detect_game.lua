-- Simple Game Detection for BizHawk with JSON Game Support
-- Main objective: Detect ROM name, validate against supported_games.json, and save to current_game file

local lastRomName = ""
local supportedGames = nil

-- Custom JSON Parser for Lua
function parseJSON(jsonString)
    -- Remove whitespace
    jsonString = jsonString:gsub("%s+", "")
    
    -- Simple JSON parser for our specific structure
    local games = {}
    local gameCount = 0
    
    -- Extract supported_games array
    local gamesPattern = '"supported_games"%s*:%s*%[(.-)%]'
    local gamesContent = jsonString:match(gamesPattern)
    
    if not gamesContent then
        print("✗ Error: Could not find supported_games array in JSON")
        return nil
    end
    
    -- Parse individual game objects
    for gameObject in gamesContent:gmatch('{.-}') do
        local game = {}
        
        -- Extract name
        local name = gameObject:match('"name"%s*:%s*"([^"]*)"')
        if name then game.name = name end
        
        -- Extract slug
        local slug = gameObject:match('"slug"%s*:%s*"([^"]*)"')
        if slug then game.slug = slug end
        
        -- Extract year
        local year = gameObject:match('"year"%s*:%s*(%d+)')
        if year then game.year = tonumber(year) end
        
        -- Extract platform
        local platform = gameObject:match('"platform"%s*:%s*"([^"]*)"')
        if platform then game.platform = platform end
        
        -- Extract description
        local description = gameObject:match('"description"%s*:%s*"([^"]*)"')
        if description then game.description = description end
        
        if game.name and game.slug then
            table.insert(games, game)
            gameCount = gameCount + 1
        end
    end
    
    print("✓ Parsed " .. gameCount .. " games from JSON")
    return games
end

-- Load supported games from JSON file
function loadSupportedGames()
    if supportedGames then
        return supportedGames -- Already loaded
    end
    
    local file = io.open("supported_games.json", "r")
    if not file then
        print("✗ Error: Could not open supported_games.json")
        return nil
    end
    
    local content = file:read("*all")
    file:close()
    
    if not content or content == "" then
        print("✗ Error: Empty or invalid JSON file")
        return nil
    end
    
    supportedGames = parseJSON(content)
    return supportedGames
end

-- Search for game by ROM name (fuzzy matching)
function findGameByRomName(romName)
    local games = loadSupportedGames()
    if not games then
        return nil
    end
    
    local romLower = string.lower(romName)
    
    -- First try exact slug match
    for _, game in ipairs(games) do
        if string.lower(game.slug) == romLower then
            return game
        end
    end
    
    -- Then try partial name matching
    for _, game in ipairs(games) do
        local nameLower = string.lower(game.name)
        if string.find(nameLower, romLower) or string.find(romLower, nameLower) then
            return game
        end
    end
    
    -- Finally try slug partial matching
    for _, game in ipairs(games) do
        local slugLower = string.lower(game.slug)
        if string.find(slugLower, romLower) or string.find(romLower, slugLower) then
            return game
        end
    end
    
    return nil
end

-- Check if a game is supported
function isGameSupported(romName)
    return findGameByRomName(romName) ~= nil
end

-- Check if game-specific module exists in games folder
function checkGameModule(gameSlug)
    if not gameSlug then
        return false, nil
    end
    
    local modulePath = "games/" .. gameSlug .. ".lua"
    local file = io.open(modulePath, "r")
    
    if file then
        file:close()
        return true, modulePath
    else
        return false, modulePath
    end
end

-- Load and run game-specific module if available
function loadAndRunGameModule(modulePath)
    -- Set flag to indicate external execution
    _G.EXTERNAL_EXECUTION = true
    
    local success, result = pcall(dofile, modulePath)
    if success then
        print("✓ Loaded and running game module: " .. modulePath)
        
        -- Check if the module has an initialization function
        if type(_G.initGameModule) == "function" then
            print("🔧 Initializing game module...")
            local initSuccess, initError = pcall(_G.initGameModule)
            if initSuccess then
                print("✓ Game module initialized successfully")
            else
                print("✗ Game module initialization failed: " .. tostring(initError))
            end
        end
        
        -- Check if the module has a main loop function
        if type(_G.gameModuleLoop) == "function" then
            print("🔄 Game module has custom loop function")
            return true, "custom_loop"
        else
            print("📝 Game module loaded, continuing with standard detection")
            return true, "standard"
        end
    else
        print("✗ Error loading game module: " .. result)
        return false, "error"
    end
end

function writeCurrentGame(romName)
    local file = io.open("current_game.txt", "w")
    if file then
        -- Try to find game info
        local gameInfo = findGameByRomName(romName)
        local outputData = romName
        
        if gameInfo then
            outputData = gameInfo.slug  -- Use official slug for consistency
            print("✓ Game recognized: " .. gameInfo.name .. " (" .. gameInfo.year .. ")")
            print("✓ Using slug: " .. gameInfo.slug)
        else
            print("⚠ Unknown game: " .. romName)
        end
        
        file:write(outputData)
        file:close()
        print("✓ Current game saved: " .. outputData)
        return true
    else
        print("✗ Error saving current game")
        return false
    end
end

function detectGame()
    local romName = gameinfo.getromname()

    -- Only update if ROM changed
    if romName ~= lastRomName then
        lastRomName = romName

        if romName and romName ~= "" then
            print("🎮 ROM detected: " .. romName)
            
            -- Check if game is supported
            if isGameSupported(romName) then
                local gameInfo = findGameByRomName(romName)
                print("✅ Supported game: " .. gameInfo.name .. " (" .. gameInfo.year .. ")")
                
                -- Check for game-specific module
                local hasModule, modulePath = checkGameModule(gameInfo.slug)
                if hasModule then
                    print("📦 Found game module: " .. modulePath)
                    local success, loopType = loadAndRunGameModule(modulePath)
                    if success then
                        if loopType == "custom_loop" then
                            print("🚀 Switching to game-specific loop for " .. gameInfo.name)
                            -- Run the custom game module loop instead of standard detection
                            runGameModuleLoop(gameInfo)
                            return -- Exit standard detection loop
                        else
                            print("🚀 Game-specific tracking enabled for " .. gameInfo.name)
                        end
                    else
                        print("⚠ Falling back to basic detection")
                    end
                else
                    print("📥 No game module found: " .. modulePath)
                    print("   Using basic detection for " .. gameInfo.name)
                    print("   Module will be downloaded automatically by BizHawk tool")
                end
            else
                print("❌ Unsupported game - scores will not be tracked")
            end
            
            writeCurrentGame(romName)
        else
            print("⚠ No ROM loaded")
            writeCurrentGame("")
        end
    end
end

-- Run game-specific module loop
function runGameModuleLoop(gameInfo)
    print("🎮 Starting game-specific loop for " .. gameInfo.name)
    
    while true do
        -- Call the game module's main loop function
        if type(_G.gameModuleLoop) == "function" then
            local success, shouldContinue = pcall(_G.gameModuleLoop)
            if not success then
                print("✗ Error in game module loop, falling back to standard detection")
                break
            end
            if shouldContinue == false then
                print("🛑 Game module requested to exit")
                break
            end
        else
            print("✗ Game module loop function not found, falling back to standard detection")
            break
        end
        
        emu.frameadvance()
        
        -- Check if ROM changed (exit game-specific loop)
        local currentRom = gameinfo.getromname()
        if currentRom ~= lastRomName then
            print("🔄 ROM changed, exiting game-specific loop")
            break
        end
    end
    
    print("↩ Returning to standard game detection")
end

-- Main loop
print("Game Detection System with JSON Support")
print("Loading supported games...")

-- Load supported games on startup
local games = loadSupportedGames()
if games then
    print("✓ Loaded " .. #games .. " supported games:")
    for _, game in ipairs(games) do
        print("  - " .. game.name .. " [" .. game.slug .. "]")
    end
else
    print("✗ Failed to load supported games - continuing with basic detection")
end

print("Watching for ROM changes...")

while true do
    detectGame()
    emu.frameadvance()
end