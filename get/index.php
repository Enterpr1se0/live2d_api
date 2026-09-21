<?php
isset($_GET['id']) ? $id = $_GET['id'] : exit('error');

require '../tools/modelList.php';
require '../tools/modelTextures.php';
require '../tools/jsonCompatible.php';

$modelList = new modelList();
$modelTextures = new modelTextures();
$jsonCompatible = new jsonCompatible();

$id = explode('-', $id);
$modelId = (int)$id[0];
$modelTexturesId = isset($id[1]) ? (int)$id[1] : 0;

$modelName = $modelList->id_to_name($modelId);
if ($modelName === false || $modelName === null || $modelName === '') exit('error: model id not found');
$isModelGroup = is_array($modelName);

if ($isModelGroup) {
    $modelName = $modelTexturesId > 0 ? $modelName[$modelTexturesId-1] : $modelName[0];
}

$modelPath = '../model/'.$modelName.'/';
$model3Files = glob($modelPath.'*.model3.json');

/* ---------- Cubism 3 / 4 / 5 模型（model3.json） ---------- */
if (!empty($model3Files)) {
    $json = json_decode(file_get_contents($model3Files[0]), 1);
    $fileReferences = &$json['FileReferences'];

    if (!$isModelGroup && $modelTexturesId > 0) {
        $modelTexturesName = $modelTextures->get_name($modelName, $modelTexturesId);
        if (isset($modelTexturesName)) $fileReferences['Textures'] = is_array($modelTexturesName) ? $modelTexturesName : array($modelTexturesName);
    }

    foreach ($fileReferences['Textures'] as $k => $texture)
        $fileReferences['Textures'][$k] = '../model/' . $modelName . '/' . $texture;

    $fileReferences['Moc'] = '../model/'.$modelName.'/'.$fileReferences['Moc'];
    foreach (array('Pose', 'Physics', 'DisplayInfo') as $key)
        if (isset($fileReferences[$key])) $fileReferences[$key] = '../model/'.$modelName.'/'.$fileReferences[$key];

    if (isset($fileReferences['Motions']))
        foreach ($fileReferences['Motions'] as $k => $v) foreach ($v as $k2 => $v2)
            if (isset($v2['File'])) $fileReferences['Motions'][$k][$k2]['File'] = '../model/'.$modelName.'/'.$v2['File'];

    if (isset($fileReferences['Expressions']))
        foreach ($fileReferences['Expressions'] as $k => $v)
            if (isset($v['File'])) $fileReferences['Expressions'][$k]['File'] = '../model/'.$modelName.'/'.$v['File'];

    header("Content-type: application/json");
    echo $jsonCompatible->json_encode($json);
    exit;
}

/* ---------- Cubism 2 模型（index.json） ---------- */
$json = json_decode(file_get_contents($modelPath.'index.json'), 1);
if (!$isModelGroup && $modelTexturesId > 0) {
    $modelTexturesName = $modelTextures->get_name($modelName, $modelTexturesId);
    if (isset($modelTexturesName)) $json['textures'] = is_array($modelTexturesName) ? $modelTexturesName : array($modelTexturesName);
}

foreach ($json['textures'] as $k => $texture)
	$json['textures'][$k] = '../model/' . $modelName . '/' . $texture;

$json['model'] = '../model/'.$modelName.'/'.$json['model'];
if (isset($json['pose'])) $json['pose'] = '../model/'.$modelName.'/'.$json['pose'];
if (isset($json['physics'])) $json['physics'] = '../model/'.$modelName.'/'.$json['physics'];

if (isset($json['motions']))
    foreach ($json['motions'] as $k => $v) foreach($v as $k2 => $v2) foreach ($v2 as $k3 => $motion)
        if ($k3 == 'file' || $k3 == 'sound') $json['motions'][$k][$k2][$k3] = '../model/' . $modelName . '/' . $motion;

if (isset($json['expressions']))
    foreach ($json['expressions'] as $k => $v) foreach($v as $k2 => $expression)
        if ($k2 == 'file') $json['expressions'][$k][$k2] = '../model/' . $modelName . '/' . $expression;

header("Content-type: application/json");
echo $jsonCompatible->json_encode($json);
