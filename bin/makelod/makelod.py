import bpy
import sys
import os
import datetime

def log(message, level="INFO"):
    print(f"[{datetime.datetime.now()}] [{level}] {message}")

def clean_scene():
    bpy.ops.object.select_all(action='SELECT')
    bpy.ops.object.delete(use_global=False)

def validate_paths(input_fbx, output_dir):
    if not os.path.isfile(input_fbx):
        log(f"Erreur : Le fichier d'entrée {input_fbx} n'existe pas.", "ERROR")
        sys.exit(1)
    if not os.path.isdir(output_dir):
        log(f"Erreur : Le répertoire de sortie {output_dir} n'existe pas.", "ERROR")
        sys.exit(1)

def create_lod_level(obj, reduction, lod_index, output_dir):
    lod_name = f"{obj.name}_LOD{lod_index}"
    lod_object = obj.copy()
    lod_object.data = obj.data.copy()
    lod_object.name = lod_name

    log(f"Création de LOD {lod_index} pour {obj.name} avec réduction {reduction*100:.2f}%", "INFO")

    decimate_modifier = lod_object.modifiers.new(name="Decimate", type='DECIMATE')
    decimate_modifier.ratio = reduction
    bpy.context.view_layer.objects.active = lod_object
    bpy.ops.object.modifier_apply(modifier="Decimate")

    export_path = os.path.join(output_dir, f"{lod_name}.fbx")
    bpy.ops.object.select_all(action='DESELECT')
    lod_object.select_set(True)
    bpy.ops.export_scene.fbx(filepath=export_path, use_selection=True, global_scale=0.01)
    log(f"LOD {lod_index} exporté sous : {export_path}", "INFO")

    return lod_object

def generate_lods(input_fbx, output_dir, lod_ratios):
    log(f"Chemin d'entrée FBX : {input_fbx}", "INFO")
    log(f"Répertoire de sortie : {output_dir}", "INFO")
    log(f"Niveaux de réduction : {lod_ratios}", "INFO")

    clean_scene()

    try:
        bpy.ops.import_scene.fbx(filepath=input_fbx)
    except Exception as e:
        log(f"Erreur lors de l'importation : {e}", "ERROR")
        sys.exit(1)

    imported_objects = bpy.context.selected_objects
    if not imported_objects:
        log("Erreur : Aucun objet n'a été importé. Vérifiez le fichier d'entrée.", "ERROR")
        sys.exit(1)

    for obj in imported_objects:
        for i, reduction in enumerate(lod_ratios, start=1):
            create_lod_level(obj, reduction, i, output_dir)

    log("Tous les niveaux de LOD ont été exportés avec succès.", "INFO")

if __name__ == "__main__":
    if len(sys.argv) < 4:
        log("Erreur : Veuillez fournir le chemin d'entrée FBX, le dossier de sortie et les niveaux de réduction.", "ERROR")
        sys.exit(1)

    input_fbx = sys.argv[-3]
    output_dir = sys.argv[-2]
    lod_ratios_string = sys.argv[-1]

    validate_paths(input_fbx, output_dir)

    try:
        lod_ratios = [float(r) / 100 for r in lod_ratios_string.split(",")]
    except ValueError:
        log("Erreur : Les niveaux de réduction doivent être des nombres séparés par des virgules.", "ERROR")
        sys.exit(1)

    generate_lods(input_fbx, output_dir, lod_ratios)
