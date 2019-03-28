<?php
require __DIR__ . '/vendor/autoload.php';

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Google Drive API PHP Quickstart');
    $client->setScopes(Google_Service_Drive::DRIVE);
    $client->setAuthConfig('credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Load previously authorized token from a file, if it exists.
    // The file token.json stores the user's access and refresh tokens, and is
    // created automatically when the authorization flow completes for the first
    // time.
    $tokenPath = 'token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }
        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }
    return $client;
}


// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Drive($client);

/************** MOVE A FILE INTO A FOLDER *******************/
//$fileId = '1QOZdFHBYf7cC8RVNV_bzN1rg9eXMqGq2';
//$folderId = '1j6os7JpweinPLn0O_zLlpoitoajMYIwX';
//$emptyFileMetadata = new Google_Service_Drive_DriveFile();
//// Retrieve the existing parents to remove
//$file = $service->files->get($fileId, array('fields' => 'parents'));
//$previousParents = join(',', $file->parents);
//// Move the file to the new folder
//$file = $service->files->update($fileId, $emptyFileMetadata, array(
//    'addParents' => $folderId,
//    'removeParents' => $previousParents,
//    'fields' => 'id, parents'));

/************ COPY A FOLDER FROM A TEMPLATE FOLDER (NOT YET COMPLETE) *****************/
//$oldFolderId = '1ty4QGhyrgFaYHMCTR6_Yufgjye-9bEgI';
//$folderId = '1Dc5sk__HWNsBDZJgiAMlarIlSYGV0uFP';
//
//$emptyFileMetadata = new Google_Service_Drive_DriveFile(array('name' => 'nice'));
////// Retrieve the existing parents to remove
////$file = $service->files->get($oldFolderId, array('fields' => 'parents'));
////$previousParents = join(',', $file->parents);
////// Move the file to the new folder
////$file = $service->files->update($oldFolderId, $emptyFileMetadata, array(
////    'addParents' => $folderId,
////    'fields' => 'id'));
////
////printf("Folder ID: %s\n", $file->id);
//
//
//$file = $service->files->copy($oldFolderId, $emptyFileMetadata);
//
//printf("Folder ID: %s\n", $file->id);

/*************** CREATE A FOLDER IN GOOGLE DRIVE *****************/
//$fileMetadata = new Google_Service_Drive_DriveFile(array(
//    'name' => 'Invoices',
//    'mimeType' => 'application/vnd.google-apps.folder'));
// 
//$file = $service->files->create($fileMetadata, array(
//    'fields' => 'id'));
//
//printf("Folder ID: %s\n", $file->id);


/********** DISPLAY CONTENTS ON GOOGLE DRIVE **************/
// Print the names and IDs for up to 10 files.
$folderId = '1ty4QGhyrgFaYHMCTR6_Yufgjye-9bEgI';
$optParams = parameter($folderId);
$results = $service->files->listFiles($optParams);
$parentId = null;

if (count($results->getFiles()) == 0) {
    print "No files found.\n";
} else {
//    print "Files:\n";
//    foreach ($results->getFiles() as $file) {     
//        if ($file->name == "Template") {
//            $parentId = $file->getId();
//        }
//         if ($file->mimeType == "application/vnd.google-apps.folder" && 
//            ($file->trashed == false || $file->trashed == null)) {
//
//            $optParams1 = $optParams = parameter($file->getId());
//
//            printf("First Child: %s (%s)\n", $file->getName(), $file->getId());
//
//            $childResults = $service->files->listFiles($optParams1);
//            foreach ($childResults->getFiles() as $childFile) {
//                 if ($childFile->mimeType == "application/vnd.google-apps.folder" && 
//                    ($childFile->trashed == false || $childFile->trashed == null)) {
//                    printf("Second Child: %s (%s)\n", $childFile->getName(), $childFile->getId());
//
//                    $optParams2 = $optParams = parameter($childFile->getId());
//
//                    $childResults2 = $service->files->listFiles($optParams2);
//                    foreach ($childResults2->getFiles() as $childFile2) {
//                        var_dump('here?');
//                        if ($childFile2->mimeType == "application/vnd.google-apps.folder" && 
//                            ($childFile2->trashed == false || $childFile2->trashed == null)) {
//                            printf("Third Child: %s (%s)\n", $childFile2->getName(), $childFile2->getId());
//                        }
//                    }
//                 }
//            }
//        }
//    }
   foreach ($results->getFiles() as $file) {
    	if ($file->name == "Template") {
            $parentId = $file->getId();
        }

        if (isCondition($file)) {
        	$childResults = getWhatever($file, $service);
        	recursiveFunction($childResults, $service);

        }
    }
}

function parameter($id) {
    return array(
        'fields' => "files(contentHints/thumbnail,fileExtension,iconLink,id,name,size,thumbnailLink,webContentLink,webViewLink,mimeType,parents)",
        'q' => "'".$id."' in parents"
    );
}

    function recursiveFunction($childResults, $service)
    {
    	foreach ($childResults->getFiles() as $childFile) {
             if ($childFile->mimeType == "application/vnd.google-apps.folder" && 
                ($childFile->trashed == false || $childFile->trashed == null)) {
                 $optParams1 = array(
                    'fields' => "files(contentHints/thumbnail,fileExtension,iconLink,id,name,size,thumbnailLink,webContentLink,webViewLink,mimeType,parents)",
                    'q' => "'".$childFile->getId()."' in parents"
                );
                $childResults2 = $service->files->listFiles($optParams1);
                printf("Second Child: %s (%s)\n", $childFile->getName(), $childFile->getId());

                recursiveFunction($childResults2, $service);
             }
        }
    }



    function getWhatever($file, $service)
    {
    	$optParams1 = array(
            'fields' => "files(contentHints/thumbnail,fileExtension,iconLink,id,name,size,thumbnailLink,webContentLink,webViewLink,mimeType,parents)",
            'q' => "'".$file->getId()."' in parents"
        );
         
        $childResults = $service->files->listFiles($optParams1);

        return $childResults;
    }

    function isCondition($file)
    {
    	if ($file->mimeType == "application/vnd.google-apps.folder" && 
            ($file->trashed == false || $file->trashed == null))
            return true;

        return false;
    }

