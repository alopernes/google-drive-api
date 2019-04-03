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


print 'Enter Job Number: ';
$jobNumber = trim(fgets(STDIN));

print 'Enter Folder Name: ';
$folderName = trim(fgets(STDIN));

print "##### Archived Start! #####\n";

if ($jobNumber && $folderName) {
    $parentFolderName = '[' . $jobNumber . '] ' . $folderName;
} else {
    print "Please input values correctly.\n";
    exit;
}

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Drive($client);

$archiveID = getenv('ARCHIVED_FOLDER_ID');
$allFiles = $service->files->listFiles();
$foundFlag = false;

if (count($allFiles->getFiles()) == 0) {
    print "No file found.\n";
    exit;
} else {
   foreach ($allFiles->getFiles() as $file) {
       if ($parentFolderName == $file->getName() && 
           ($file->mimeType == "application/vnd.google-apps.folder" && 
            ($file->trashed == false || $file->trashed == null))) {
            $foundFlag = true;
            $emptyFileMetadata = new Google_Service_Drive_DriveFile();
            // Retrieve the existing parents to remove
            $parentData = $service->files->get($file->getId(), array('fields' => 'parents'));
            $previousParents = join(',', $parentData->parents);
            // Move the file to the new folder
            $service->files->update($file->getId(), $emptyFileMetadata, array(
                'addParents' => $archiveID,
                'removeParents' => $previousParents,
                'fields' => 'id, parents'));

           print "##### Archived Successfully: ". $file->getName() ."\n";
           exit;
       }
   }
    
   if (!$foundFlag) {
       print "No file found.\n";
       exit;
   }
}