<?php
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();

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

function parameter($id) {
    return array(
        'fields' => "files(contentHints/thumbnail,fileExtension,iconLink,id,name,size,thumbnailLink,webContentLink,webViewLink,mimeType,parents)",
        'q' => "'".$id."' in parents"
    );
}

function getWhatever($file, $service) {
    $optParams1 = parameter($file->getId());

    $childResults = $service->files->listFiles($optParams1);

    return $childResults;
}

function isCondition($file) {
    if ($file->mimeType == "application/vnd.google-apps.folder" && 
        ($file->trashed == false || $file->trashed == null))
        return true;

    return false;
}

function createFolders($file, $service, $parentFolderId, $init = null) {
    $fileName = $init ? $file : $file->getName();
    $fileMetadata = new Google_Service_Drive_DriveFile(array(
        'name' => $fileName,
        'mimeType' => 'application/vnd.google-apps.folder',
        'parents' => [$parentFolderId]
    ));

    $createdFile = $service->files->create($fileMetadata, array('fields' => 'id'));

    printf("Generating Folder: %s\n", $fileName);
    return $createdFile->id;
}

function copyFiles($fileId, $service, $parentFolderId) {
    $emptyFileMetadata = new Google_Service_Drive_DriveFile(array(
        'parents' => [$parentFolderId]
    ));

    $file = $service->files->copy($fileId, $emptyFileMetadata);

    return $file->id;
}

function recursiveFunction($childResults, $service, $parentFolderId) {
    foreach ($childResults->getFiles() as $childFile) {
         if (isCondition($childFile)) {
            $createSubFolderId = createFolders($childFile, $service, $parentFolderId);
            $optParams1 = parameter($childFile->getId());
            $childResults2 = $service->files->listFiles($optParams1);

            recursiveFunction($childResults2, $service, $createSubFolderId);
         } else {
             printf("Copying File: %s\n", $childFile->getName());
             copyFiles($childFile->getId(), $service, $parentFolderId);
        }
    }
}

print 'Enter Job Number: ';
$jobNumber = trim(fgets(STDIN));

print 'Enter Folder Name: ';
$folderName = trim(fgets(STDIN));

print "##### STARTING #####.\n";

if ($jobNumber && $folderName) {
    $parentFolderName = '[' . $jobNumber . '] ' . $folderName;
} else {
    print "Please input values correctly.\n";
    exit;
}

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Drive($client);

$mainFolderId = getenv('MAIN_FOLDER_ID');
$folderUrl = getenv('DRIVE_FOLDER_URL');
$templateId = getenv('TEMPLATE_FOLDER_ID');
$optParams = parameter($templateId);

$results = $service->files->listFiles($optParams);
$allFiles = $service->files->listFiles(['q' => 'trashed=false']);

if (count($allFiles->getFiles()) == 0) {
    print "No files found.\n";
    exit;
} else {
    foreach ($allFiles->getFiles() as $file) {
        if ($parentFolderName == $file->getName() && isCondition($file)) {
            print "Folder already exists.\n";
            exit;
        }
    }

    $parentFolderId = createFolders($parentFolderName, $service, $mainFolderId, true);

    if (count($results->getFiles()) == 0 && is_null($parentFolderId)) {
        print "No files found.\n";
        exit;
    } else {
       foreach ($results->getFiles() as $file) {
            if (isCondition($file)) {
                $createSubFolderId = createFolders($file, $service, $parentFolderId);
                $childResults = getWhatever($file, $service);
                recursiveFunction($childResults, $service, $createSubFolderId);
            } else {
                printf("Copying File: %s\n", $file->getName());
                copyFiles($file->getId(), $service, $parentFolderId);
            }
        }
        print "##### Created successfully! #####\n";
        print "##### Drive URL : " . $folderUrl . $parentFolderId . "\n";
        
        exit;
    }
}