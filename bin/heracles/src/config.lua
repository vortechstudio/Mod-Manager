-- config.lua

local config = {}

config.settings = {
    output_suffix = "_obfuscated.lua",
    watermark_enabled = true,
    final_print = true,
    control_flow = {
        enabled = true,
        max_fake_blocks = 6,
    },
    string_encoding = {
        enabled = true,
    },
    variable_renaming = {
        enabled = true,
        min_name_length = 8,
        max_name_length = 16,
    },
    garbage_code = {
        enabled = true,
        garbage_blocks = 4,
    },
    opaque_predicates = {
        enabled = true,
    },
    function_inlining = {
        enabled = true,
    },
    dynamic_code = {
        enabled = true,
    },
    bytecode_encoding = {
        enabled = false,
    },
    compressor = {
        enabled = true,
    }
}

function config.get(key)
    local keys = {}
    for k in key:gmatch("[^.]+") do table.insert(keys, k) end

    local value = config
    for _, k in ipairs(keys) do
        value = value[k]
        if value == nil then
            return nil
        end
    end
    return value
end

function config.set(key, new_value)
    local keys = {}
    for k in key:gmatch("[^.]+") do table.insert(keys, k) end

    local value = config
    for i = 1, #keys - 1 do
        value = value[keys[i]]
        if value == nil then
            return false
        end
    end

    local last_key = keys[#keys]
    if value[last_key] ~= nil then
        value[last_key] = new_value
        return true
    else
        return false
    end
end

return config