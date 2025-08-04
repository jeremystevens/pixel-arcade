-- BizHawk Lua Script for Donkey Kongs Final Score Capture
-- Monitors Player 1's lives and captures final score when lives reach zero
-- Saves high scores to JSON Lines format with player initials
-- Author: Jeremy Stevens
-- Date: July 29, 2025

local PLAYER1_SCORE_ADDRS = { 0x0025, 0x0026, 0x0027 }
local PLAYER1_GAME_OVER_ADDR = 0x0406
local PLAYER1_ZONE_ADDR = 0x0053 -- 1-3 zone indicator
local ZONE3_COMPLETION_ADDR = 0x0054

-- Game Genie cheat detection addresses
local CHEAT_ADDRS = {
    {addr = 0x89F1, value = 0xA5, name = "Infinite Lives"},
    {addr = 0x88E7, value = 0x09, name = "Start with 9 Lives"},
    {addr = 0x8DE6, value = 0x0D, name = "Controllable Jump"},
    {addr = 0x954B, value = 0x4B, name = "Keep Hammer Longer"}
}

local score_captured = false
local entry_mode = false
local initials = {"", "", ""}
local current_letter = 1
local blink_timer = 0
local prev_keys = {}

-- Wait for ROM
while not pcall(function() memory.read_u8(0x0000) end) do
    gui.text(10, 10, "Waiting for ROM to load...", "white", "black")
    emu.frameadvance()
end

local function json_encode_string(str)
    str = str:gsub("\\", "\\\\"):gsub('"', '\"')
    str = str:gsub("\n", "\\n"):gsub("\r", "\\r"):gsub("\t", "\\t")
    return '"' .. str .. '"'
end

local function get_iso_timestamp()
    local t = os.date("*t")
    return string.format("%04d-%02d-%02dT%02d:%02d:%02d.000000Z",
        t.year, t.month, t.day, t.hour, t.min, t.sec)
end

local function read_bcd_score()
    local digits = {}
    for _, addr in ipairs(PLAYER1_SCORE_ADDRS) do
        local byte = memory.read_u8(addr)
        local hi = (byte >> 4) & 0x0F
        local lo = byte & 0x0F
        table.insert(digits, string.format("%X", hi))
        table.insert(digits, string.format("%X", lo))
    end
    return tonumber(table.concat(digits))
end

local function get_zone()
    return memory.read_u8(PLAYER1_ZONE_ADDR)
end

local function get_zone3_completion()
    return memory.read_u8(ZONE3_COMPLETION_ADDR)
end

local function check_game_genie_codes()
    local codes_active = {}
    
    -- TEMPORARILY DISABLED - Getting false positives
    -- Need to research when these values naturally occur vs. when they're cheat-modified
    
    --[[
    for _, cheat in ipairs(CHEAT_ADDRS) do
        local current_value = memory.read_u8(cheat.addr)
        if current_value == cheat.value then
            codes_active[cheat.name] = true
        end
    end
    --]]
    
    return codes_active
end

local function write_score(score)
    local obj = {
        game = "Donkey Kong (NES)",
        initials = table.concat(initials),
        score = score,
        timestamp = get_iso_timestamp()
    }
    
    local parts = {}
    for k,v in pairs(obj) do
        table.insert(parts, json_encode_string(k) .. ":" .. json_encode_string(tostring(v)))
    end
    local json = "{" .. table.concat(parts, ",") .. "}"
    local file = io.open("highscores.jsonl", "a")
    if file then
        file:write(json .. "\n")
        file:close()
        console.log("✅ Score saved: " .. table.concat(initials) .. " - " .. score)
    else
        console.log("❌ Error: Could not write to highscores.jsonl")
    end
end

local function is_key_pressed(key, current_keys)
    return current_keys[key] and not prev_keys[key]
end

local function handle_keyboard_input()
    local keys = input.get()
    
    -- Handle letter input (A-Z)
    for i = string.byte('A'), string.byte('Z') do
        local letter = string.char(i)
        if is_key_pressed(letter, keys) then
            initials[current_letter] = letter
            -- Auto-advance to next position
            if current_letter < 3 then
                current_letter = current_letter + 1
            end
        end
    end
    
    -- Handle lowercase letters too
    for i = string.byte('a'), string.byte('z') do
        local letter = string.char(i):upper()
        if is_key_pressed(string.char(i), keys) then
            initials[current_letter] = letter
            -- Auto-advance to next position
            if current_letter < 3 then
                current_letter = current_letter + 1
            end
        end
    end
    
    -- Navigation keys
    if is_key_pressed("Left", keys) and current_letter > 1 then
        current_letter = current_letter - 1
    end
    
    if is_key_pressed("Right", keys) and current_letter < 3 then
        current_letter = current_letter + 1
    end
    
    -- Backspace - clear current letter and move back
    if is_key_pressed("Backspace", keys) then
        initials[current_letter] = ""
        if current_letter > 1 then
            current_letter = current_letter - 1
        end
    end
    
    -- Delete - clear current letter but stay in position
    if is_key_pressed("Delete", keys) then
        initials[current_letter] = ""
    end
    
    -- Space - clear current letter and advance
    if is_key_pressed("Space", keys) then
        initials[current_letter] = ""
        if current_letter < 3 then
            current_letter = current_letter + 1
        end
    end
    
    -- Enter - submit if all initials are filled
    if is_key_pressed("Return", keys) or is_key_pressed("Enter", keys) then
        local all_filled = true
        for i = 1, 3 do
            if initials[i] == "" then
                all_filled = false
                break
            end
        end
        
        if all_filled then
            local score = read_bcd_score()
            write_score(score)
            entry_mode = false
            score_captured = true
            console.log("🎉 Initials saved: " .. table.concat(initials))
        else
            console.log("⚠️ Please enter all 3 initials")
        end
    end
    
    -- Escape - cancel entry
    if is_key_pressed("Escape", keys) then
        entry_mode = false
        score_captured = true
        console.log("❌ Initial entry cancelled")
    end
    
    -- Store current keys for next frame
    prev_keys = {}
    for k, v in pairs(keys) do
        prev_keys[k] = v
    end
end

console.clear()
console.log("📝 Controls: Type letters, Use Arrow Keys, Backspace, Enter to submit, Esc to cancel")
console.log("✅ All scores will be saved normally")

while true do
    local game_over = memory.read_u8(PLAYER1_GAME_OVER_ADDR)

    -- Check for game over and start initial entry (only if no cheats active)
    if not entry_mode and game_over == 1 and not score_captured then
        local active_codes = check_game_genie_codes()
        if next(active_codes) == nil then
            -- No cheats detected - allow score entry
            entry_mode = true
            initials = {"", "", ""}
            current_letter = 1
            prev_keys = {}
            console.log("🎮 Game Over! Enter your initials...")
        else
            -- Cheats detected - block score entry
            score_captured = true -- Mark as captured to prevent future attempts
            console.log("❌ Score NOT saved - Game Genie codes detected!")
            for code_name, _ in pairs(active_codes) do
                console.log("   • " .. code_name)
            end
        end
    end
    
    -- Reset when game starts again
    if game_over == 0 and score_captured then
        score_captured = false
        console.log("🔄 New game started")
    end

    if entry_mode then
        handle_keyboard_input()
        
        -- Simple initial entry display
        gui.text(10, 10, "ENTER INITIALS:", "yellow", "black")
        
        -- Display initials with cursor
        local display = ""
        for i = 1, 3 do
            local letter = initials[i] ~= "" and initials[i] or "_"
            
            -- Highlight current position
            if i == current_letter then
                if blink_timer < 30 then
                    display = display .. "[" .. letter .. "] "
                else
                    display = display .. " " .. letter .. "  "
                end
            else
                display = display .. " " .. letter .. "  "
            end
        end
        
        blink_timer = (blink_timer + 1) % 60
        gui.text(10, 30, display, "white", "black")
        
    else
        -- Only show current score during gameplay
        local score = read_bcd_score()
        gui.text(10, 10, "Score: " .. tostring(score), "white", "black")
    end

    emu.frameadvance()
end