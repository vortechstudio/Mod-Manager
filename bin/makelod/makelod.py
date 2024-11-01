import bpy
import sys
import os

def make_object_single_user(obj):
    obj.data = obj.data.copy()

def generate_lods(input_fbx, output_dir):
    print(f"Chemin d'entrée FBX : {input_fbx}")
    print(f"Répertoire de sortie : {output_dir}")

    # Importer le fichier FBX
    bpy.ops.import_scene.fbx(filepath=input_fbx)
    imported_objects = bpy.context.selected_objects
    print(f"Nombre d'objets importés : {len(imported_objects)}")

    if not imported_objects:
        print("Erreur : Aucun objet n'a été importé. Vérifiez le fichier d'entrée.")
        return

    for obj in imported_objects:
        bpy.context.view_layer.objects.active = obj
        obj.select_set(True)
        bpy.ops.object.transform_apply(location=True, rotation=True, scale=False)

        lod_levels = [0.8, 0.65, 0.45]
        for i, reduction in enumerate(lod_levels, start=1):
            lod_collection_name = f"LOD{i}"
            lod_collection = bpy.data.collections.new(lod_collection_name)
            bpy.context.scene.collection.children.link(lod_collection)

            for obj in imported_objects:
                lod_object = obj.copy()
                lod_object.data = obj.data.copy()
                lod_object.name = f"{obj.name}_LOD{i}"
                print(f"LOD {i} créé pour l'objet {obj.name} avec réduction de {reduction * 100}%")

                # Ajouter le LOD à la collection spécifique
                lod_collection.objects.link(lod_object)

                # Appliquer le modificateur Decimate
                decimate_modifier = lod_object.modifiers.new(name="Decimate", type='DECIMATE')
                decimate_modifier.ratio = reduction
                decimate_modifier.use_collapse_triangulate = True
                bpy.context.view_layer.objects.active = lod_object
                bpy.ops.object.modifier_apply(modifier="Decimate")

                weighted_normals_modifier = lod_object.modifiers.new(name="WeightedNormals", type='WEIGHTED_NORMAL')
                bpy.ops.object.modifier_apply(modifier="WeightedNormals")

            # Exporter la collection LOD en fichier FBX
            lod_filepath = os.path.join(output_dir, f"lod{i}.fbx")

            # Sélectionner uniquement les objets de la collection courante pour l'exportation
            bpy.ops.object.select_all(action='DESELECT')
            for obj in lod_collection.objects:
                obj.select_set(True)

            bpy.ops.export_scene.fbx(filepath=lod_filepath, use_selection=True, global_scale=0.01)
            print(f"LOD {i} exporté sous : {lod_filepath}")

        print("Tous les niveaux de LOD ont été exportés.")

if len(sys.argv) < 3:
    print("Erreur : Veuillez fournir le chemin d'entrée FBX et le dossier de sortie.")
    sys.exit(1)

input_fbx = sys.argv[-2]
output_dir = sys.argv[-1]

if not os.path.isfile(input_fbx):
    print(f"Erreur : Le fichier d'entrée {input_fbx} n'existe pas.")
    sys.exit(1)

if not os.path.isdir(output_dir):
    print(f"Erreur : Le répertoire de sortie {output_dir} n'existe pas.")
    sys.exit(1)

generate_lods(input_fbx, output_dir)
