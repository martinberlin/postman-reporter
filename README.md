## Postman testing standalone HTML generator and newman reports parser

<a href="https://www.getpostman.com/"><img src="https://raw.githubusercontent.com/postmanlabs/postmanlabs.github.io/develop/global-artefacts/postman-logo%2Btext-320x132.png" /></a><br />

The intention of this simple PHP scripts is to generate a Standalone HTML for your Postman tests that you can send to the client without the need to upload all the tests in the open internet.

We find Postman a great tool for testing APIs and any web content in general.

### How to use 

Simply export your Collection with Postman to the directory where you cloned this repo.
Update the collection name in postman-collection-documentor.php

    $jsonFilename = 'Your.postman_collection.json';
    
And execute postman-collection-documentor anywhere in your intranet to see it on a browser.

If you want to save the html to send per Email, then simply run:

    php postman-collection-documentor.php >example/skygate-documentation.html
    
    
There is in the examples folder the resultant html and also a screenshot.


Now there is another feature if you need to automatize this tests and save the results in a DB that is:
postman-run-importer.php

This requires to create 2 Tables and a database. Put this DB credentials directly in the file.
When executed it will read the /newman generated files when you set the report output to json and import them in the DB.

    newman run Your.postman_collection.json -e env.json -r json

Imports the file in 2 tables, report-resume and report (contains a row per test)
At the moment is a simple example that supports only saving the same test one time per hour, but is a good start to make something more powerful.

### How to customize the HTML

There are 3 templates, one includes the other :
   
    1 - MAIN:  postman-collection-template
    2 - Folder postman-folder-loop
    3 - Item   postman-item-loop (Every test)
    
Editing the HTML there you can easily customize this to any other layout.

NOTE: This parser expects that test are organized in Folders. If they are not, then the postman-collection-documentor.php should be modified accordingly.

Please share your own corrections and reporting to contribute in the mission. Thanks for your interest,

Martin [Fasani](https://fasani.de)
