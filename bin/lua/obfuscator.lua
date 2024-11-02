-- Générer des noms de variables aléatoires pour l'obfuscation
local function generateRandomName(length)
    local chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    local name = ""
    for i = 1, length do
        local randIndex = math.random(1, #chars)
        name = name .. chars:sub(randIndex, randIndex)
    end
    return name
end

-- Fonction d'obfuscation des noms de variables et de fonctions
local function obfuscateNames(code)
    local replacements = {}
    local counter = 1

    -- Détecter les noms de variables et de fonctions sans affecter les mots-clés Lua
    for word in code:gmatch("%f[%w_][%a_][%w_]*%f[%W]") do
        if not replacements[word] and not word:match("^(if|then|end|for|while|do|local|return|function|elseif|else|repeat|until)$") then
            replacements[word] = generateRandomName(8)
            counter = counter + 1
        end
    end

    -- Remplacer les noms dans le code
    for original, obfuscated in pairs(replacements) do
        code = code:gsub("%f[%w_]" .. original .. "%f[%W]", obfuscated)
    end

    return code
end

-- Fonction pour minifier le code Lua
local function minify(code)
    -- Supprimer les commentaires
    code = code:gsub("%-%-.-\n", "")
    -- Supprimer les espaces, retours à la ligne et tabulations en trop
    code = code:gsub("%s+", " ")
    code = code:gsub("%s*([%(%),=+%-*/])%s*", "%1")
    return code
end

-- Obfuscation complète du fichier
local function obfuscateFile(inputFile, outputFile)
    -- Lire le fichier d'entrée
    local file = io.open(inputFile, "r")
    if not file then
        print("Erreur de lecture du fichier : " .. inputFile)
        return
    end

    local code = file:read("*a")
    file:close()

    -- Appliquer l'obfuscation
    code = obfuscateNames(code)
    code = minify(code)

    -- Écrire le code obfusqué dans le fichier de sortie
    file = io.open(outputFile, "w")
    if not file then
        print("Erreur de création du fichier : " .. outputFile)
        return
    end

    file:write(code)
    file:close()
    print("Obfuscation terminée : " .. outputFile)
end

-- Exécution du script avec fichiers d'entrée et de sortie
local inputFile = arg[1] or "example.lua"         -- fichier Lua à obfusquer
local outputFile = arg[2] or "example_obf.lua"    -- fichier de sortie obfusqué

obfuscateFile(inputFile, outputFile)
