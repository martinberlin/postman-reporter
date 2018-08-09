<?php
/**
 * User: martin
 * Date: 31.07.18
 TO RUN This parser, before get the console command to SPIT Json:

 newman run your_fucking_collection.json -d environment_variables.json -r json

 -r json  delivers a newman/run-report.json that is about 1 Mega per test since includes all the fucking stream response per byte json_encoded
 (Maybe the folks though we can check an image pixel by pixel, or discover faces in gravatars ;)

 If you want to delete this JSON after parsing it, just set the right permissions to the directory:
  sudo chmod -R 777 newman/
  sudo chown -R www-data:www-data newman/

  (Or whatever your apache user is)
 */
// DB CONFIG
$hostname='localhost';
$username='root';
$password='your_password';
$dbname = 'postman-run';

$deleteJsonAfterParsing = true; // Deletes the json after importing it
$directory = "newman/";         // So it seems to be called the directory by default
$files = scandir ($directory);
$jsonFilename = $directory . $files[2];
$jsonFile = file_get_contents($jsonFilename);

$jsonArray = json_decode($jsonFile);

try {
    $db = new PDO("mysql:host=$hostname;dbname=$dbname",$username,$password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to Database<br/>Ready to parse tests on file: $jsonFilename";
} catch(PDOException $e) {
    echo $e->getMessage();
}

$results = array();

$timings = $jsonArray->run->timings;

$results['collection'] = $jsonArray->collection->info->name;
$results['day'] = date('Y-m-d', $timings->started/1000); // Timestamps are in miliseconds
$results['hour'] = date('H', $timings->started/1000);   // could not get right hour with: $timings->started/1000
$results['minutes'] = date('i', $timings->started/1000);
$results['ms_avg'] = $timings->responseAverage;
$stmt = $db->prepare("INSERT INTO `$dbname`.`report-resume` (`collection`, `day`, `hour`, `minute`, `ms_avg`) VALUES
                          (:collection, :day, :hour, :minute, :ms_avg) ON DUPLICATE KEY UPDATE
                          ms_avg=:ms_avg ");
$stmt->bindParam(':collection', $results['collection']);
$stmt->bindParam(':day', $results['day']);
$stmt->bindParam(':hour', $results['hour'] );
$stmt->bindParam(':minute', $results['minutes']);
$stmt->bindParam(':ms_avg', $results['ms_avg']);

try{
    $stmt->execute();

} catch (Exception $e) {
    print_r($e);
}

echo "<h1>{$results['collection']} test resume updated on `$dbname`.`report-resume` Table</h1>";
print('<pre>');//print_r($timings->executions);
$responseTime = array();
$responseSize = array();
$testsImported = 0;
/**
 * DEBUG
 */


foreach ($jsonArray->run->executions as $e) {
    $testUrl = implode('/', $e->request->url->path);
    $results['test'] = $e->item->name;
    $responseTime[$results['test']] = $e->response->responseTime;
    $responseSize[$results['test']] = $e->response->responseSize;


    $stmt = $db->prepare("INSERT INTO `$dbname`.`report` 
                    (`collection`, `day`, `hour`, `minute`, `test`, `url`, `responseCode`, `responseTime`, `responseSize`) VALUES
                          (:collection, :day, :hour, :minute, :test, :url, :responseCode, :responseTime, :responseSize) ON DUPLICATE KEY UPDATE
                          minute=:minute, url=:url, responseCode=:responseCode, responseTime=:responseTime, responseSize=:responseSize ");
    $stmt->bindParam(':collection', $results['collection']);
    $stmt->bindParam(':day', $results['day']);
    $stmt->bindParam(':hour', $results['hour'] );
    $stmt->bindParam(':minute', $results['minutes']);
    $stmt->bindParam(':test', $results['test']);
    $stmt->bindParam(':url', $testUrl);
    $stmt->bindParam(':responseCode', $e->response->code);
    $stmt->bindParam(':responseTime', $e->response->responseTime);
    $stmt->bindParam(':responseSize', $e->response->responseSize);

    try{
        $stmt->execute();
        $testsImported++;
    } catch (Exception $e) {
        print_r($e);
    }
}
echo "<h1>$testsImported tests updated on `$dbname`.`report` Table</h1>";

echo "<h2>Test name -> Response time</h2>";
print_r($responseTime);
echo "<h2>Test name -> Response size</h2>";
print_r($responseSize);

if ($deleteJsonAfterParsing) {
    //chmod($jsonFilename, 0755);
    unlink($jsonFilename);
}

/**
 * DB table to save test results
 * --
-- Table structure for table `report`
--
CREATE TABLE IF NOT EXISTS `report` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`collection` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
`day` date NOT NULL,
`hour` int(11) NOT NULL,
`minute` int(11) NOT NULL,
`test` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
`url` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
`responseCode` int(11) NOT NULL,
`responseTime` int(11) NOT NULL,
`responseSize` int(11) NOT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `collection-day-hour` (`collection`,`test`,`day`,`hour`) COMMENT 'debug: save only one test per hour'
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

 // To extract the fucking resume
CREATE TABLE IF NOT EXISTS `report-resume` (
`id` int( 11 ) NOT NULL AUTO_INCREMENT ,
`collection` varchar( 200 ) COLLATE utf8_unicode_ci DEFAULT NULL ,
`day` date NOT NULL ,
`hour` int( 11 ) NOT NULL ,
`minute` int( 11 ) NOT NULL ,
`ms_avg` int( 11 ) NOT NULL ,
PRIMARY KEY ( `id` ) ,
UNIQUE KEY `collection-day-hour` ( `collection` , `day` , `hour`, minute ) COMMENT 'debug: save only one test per hour'
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci

 */
/**
 * TO GET Averages per hour simply run:
SELECT DAY , HOUR , collection, sum( responseTime ) / count( * ) AVG_microseconds, count(*) Tests_runned
FROM `report`
GROUP BY DAY , HOUR , collection
 */
