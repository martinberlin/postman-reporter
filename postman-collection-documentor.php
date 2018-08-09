<?php
/**
 * User: Martin working for SkyGate GmbH
 * Date: 08.08.18
 * Parses the collection.json file and delivers some *automatic* self-written documentation
 */

// Collection name. Update this to your collection name or just make a scan for any _collection.json file present
$jsonFilename = 'SkygateWebsite.postman_collection.json';

$templateFilename           = 'postman-collection-template.html';
$templateFolderLoopFilename = 'postman-folder-loop-template.html';
$templateItemLoopFilename   = 'postman-item-loop-template.html';
$templateMain = file_get_contents($templateFilename);
$templateFolderLoop = file_get_contents($templateFolderLoopFilename);
$templateItemLoop = file_get_contents($templateItemLoopFilename);
$jsonFile = file_get_contents($jsonFilename);
$jsonArray = json_decode($jsonFile);

$doc = array();
$doc['collection_name'] =  $jsonArray->info->name;
$folderLoop = "";
// TEST-Loop MAIN loop
$collapseIndex = 0;
foreach ($jsonArray->item as $i) {
    $folderContent = "";
    $folderContent = str_replace("{{folder_name}}", $i->name, $templateFolderLoop);

    // echo "<pre>";print_r($i);exit();
    // ITEM-Loop recurse every test inside the folder
    $itemLoop = "";

    foreach ($i->item as $it) {
        $itemContent = str_replace("{{test_name}}", $it->name, $templateItemLoop);
        if (isset($it->request->auth->type) && $it->request->auth->type !== 'noauth') {
            $itemContent = str_replace("{{request_auth}}", 'Authentication: '. $it->request->auth->type, $itemContent);
        } else {
            $itemContent = str_replace("{{request_auth}}", '', $itemContent);
        }
        if (isset($it->request->body->raw) && $it->request->body->raw !== '') {
            $itemContent = str_replace("{{request_body}}", '<div style="color:gray">Body: '.$it->request->body->raw.'</div>', $itemContent);
        } else {
            $itemContent = str_replace("{{request_body}}", "", $itemContent);
        }

        $requestURL = '<div style="background-color:darkgray;color:white;font-weight:bold;padding-left:0.2em">'. $it->request->method.': '.$it->request->url->raw. '</div>';
        $itemContent = str_replace("{{request_url}}", $requestURL, $itemContent);
        // EVENT-Loop recurse every item inside the folder
        foreach ($it->event as $ev) {
            $itemScript = "";

            // EVENT-Loop recolect scripts run for every test
            foreach ($ev->script->exec as $js) {
                if (substr($js,0,2) == "//") {
                    $collapseIndex++;
                    $itemScript .= '<span style="color:black;font-weight: bold">';
                    $itemScript .= $js."</span><br>";
                    $itemScript .= '<button type="button" class="btn btn-sm btn-light" data-toggle="collapse" data-target="#e'.$collapseIndex.'" title="expand">+</button>
                            <div id="e'.$collapseIndex.'" class="collapse">';
                    continue;
                }
                $itemScript .= $js."<br>";
            }
            $itemScript .= "</div>";
            $itemScript .= "<hr><br>";

            $itemContent = str_replace("{{script}}", $itemScript, $itemContent);
            $itemLoop .= $itemContent;
        }
    }
    $folderContent = str_replace("{{item_loop}}", $itemLoop, $folderContent);

    $folderLoop .= $folderContent;
}

// Populate template variables
$template = str_replace("{{collection_name}}", $doc['collection_name'], $templateMain);
$template = str_replace("{{folder_loop}}", $folderLoop, $template);

//echo "<pre>";print_r($doc);exit();
echo $template;
