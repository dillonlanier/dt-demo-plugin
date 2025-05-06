------- README for Data Tables demo plugin -------------
------- Updated 5/5/25 by Dillon Lanier.
------- This code is free to use for non-commercial puposes.
--------------------------------------------------------
--------------------------------------------------------
---- How to Use
--------------------------------------------------------
This folder contains all the necessary elements of a 
functional WordPress plugin. To use the plugin on your
WordPress site you must:
0. create a table in your sites database using the SQL command 
	shown at the bottom of this README. 
1. upload the plugin 
2. add the shortcode to the desired page ('data_table_demo')
--------------------------------------------------------
---- Purpose of the plugin
--------------------------------------------------------
This demo Data Tables plugin shows a working example of
the open source Data Tables package integrated into a 
custom WordPress plugin. The plugin uses REST and AJAX
to smoothly save and display user entered data. 
--------------------------------------------------------
---- SQL Commands
--------------------------------------------------------
*NOTE* You will need to replace 'YOUR_DATABASE_NAME' with 
the name of your WordPress database.

To execute this one-time setup SQL command it is easiest to
login to MyPHPadmin, select the database, and then execute
custom SQL. 

Setup command:
CREATE TABLE `YOUR_DATABASE_NAME`.`dt_demo_plugin_table` (`id` INT AUTO_INCREMENT PRIMARY KEY, `artist` VARCHAR(50) NULL DEFAULT NULL , `album` VARCHAR(50) NULL DEFAULT NULL , `release_date` DATE NULL DEFAULT NULL , `length` VARCHAR(20) NULL DEFAULT NULL , `number_songs` INT NULL DEFAULT NULL , `rating` DECIMAL(10,2) NULL DEFAULT NULL , `account_id` INT NULL DEFAULT NULL , `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , INDEX `account_id` (`account_id`)) ENGINE = InnoDB
