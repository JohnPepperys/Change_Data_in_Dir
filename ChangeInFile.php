<?php

/* ------------------------------------------------------------------------------------------------------------------
// Project:     Change Data In Folder
// File:        ChangeInFile.php
// Description: Main file in project


// Author:      O.Trushman
// Data:        26/10/2021
 --------------------------------------------------------------------------------------------------------------- */

declare (strict_types = 1);

class myLogSubsystem
{
    private static $fp = NULL;

    public function __construct()
    {
        if (self::$fp == NULL) {
            self::$fp = fopen(LOG_FILE_NAME, 'a');
            if (self::$fp == NULL) {
                printf("Can`not open Log file: %s. Error. Script work without logging.\n", LOG_FILE_NAME);
                return;
            }

            fprintf(self::$fp, "%s, Start script. Begin folder: %s. Find value: %s, replace to %s.\r\n",
                date("Y-m-d H:i:s"), START_FOLDER, OLD_VALUE, NEW_VALUE);
        }
    }

    public function Wr (string $mess) {
        if (self::$fp != NULL) {
            fprintf(self::$fp, "%s, %s\r\n", date("Y-m-d H:i:s"), $mess);
        }
    }

    public function Closelog () {
        if (self::$fp != NULL) {
            fprintf(self::$fp, "%s, End script\r\n\n", date("Y-m-d H:i:s"));
            fclose(self::$fp);
        }
    }
}   // -------------------------------------- end class myLogSubsystem ------------------------------------ //
//
//


class myChangeData
{
        private static $filecount = 0;
        private static $directorycount = 0;
        private static $filechanged = 0;
        private static $changes_in_file = 0;
        private static $sym = '/';
        private static $log = NULL;

        public function __construct()
        {
            // start log in class
            self::$log = new myLogSubsystem();

            // check OS - make normal slash
            self::myDetectOs();
        }

        private function myDetectOs () {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ) {
                self::$sym = '\\';
            }
            return;
        }


        private function myChangeDataInFile(string $fname) {
            // check input parameter
            if (strlen($fname) < 1) {
                return false;
            }

            $ct = 0;
            // read data from file
            $fdata = file_get_contents($fname);
            if ($fdata === false) {
                printf("Error: cannot read file %s by func file_get_contents\n", $fname);
                self::$log->Wr("Error!!! Cannot read file $fname by func. file_get_contents");
                return false;
            }

            $fdatanew = str_replace(OLD_VALUE, NEW_VALUE, $fdata, $ct);
            if ($ct > 0) {
                if (PRINT_IN_CONSOLE) {
                    printf("file: %s, replace num: %d\n", $fname, $ct); }

                $res = file_put_contents($fname, $fdatanew);
                if ($res === FALSE) {
                    printf("Error write file error\n");
                    self::$log->Wr("Error write modify file $fname.");
                    return false;
                }
                self::$log->Wr(sprintf("file: %s, replace num: %d", $fname, $ct));
                self::$filechanged++;
                self::$changes_in_file += $ct;
            }
            return true;
        }


        public function myScanDirectory(string $start_folder) {
            // check input parameter
            if (strlen($start_folder) < 1) {
                echo "Function myScanDirectory - invalid input param -$start_folder-\n";
                return false;
            }

            // make normal path for input dir
            $dir = realpath($start_folder);
            if ($dir == false) {
                echo "Find real_path for directory: $start_folder - error!!!\n";
                self::$log->Wr("Error - can`t find folder $start_folder.");
                return false;
            }

            // scan directory
            $filelist = scandir($dir);
            if ($filelist == false) {
                echo "Find scandir function for directory: $dir - error!!!\n";
                self::$log->Wr("Error - find directory $dir for scan.");
                return false;
            }

            if (PRINT_IN_CONSOLE) {
                printf("Work with directory: %s\n", $dir);
            }
            self::$log->Wr("Work with directory: $dir");

            // work with each element from dir
            foreach ($filelist as $value) {
                // no need point
                if ($value == '..' or $value == '.') {
                    continue;
                }

                // !NO work with our file
                //printf ("F: -%s-\n", __FILE__);
                if ($value === 'config.php') {
                    continue;
                }

                // if object is directory
                if (is_dir($dir. self::$sym . $value)) {
                    self::$directorycount++;
                    self::myScanDirectory($dir. self::$sym . $value);
                }
                else {
                    // but - if object is file
                    self::$filecount++;
                    self::myChangeDataInFile($dir. self::$sym . $value);
                    // printf("files: %s\n", $dir. self::$sym . $value);
                }
            }
            return true;
        }


        public function myOutputStatistic() {
            printf("\tScan all directory: %d\n", self::$directorycount);
            printf("\tScan all file: %d\n", self::$filecount);
            printf("\tChanged files: %d\n", self::$filechanged);
            printf("\tMade changes in files: %d\n", self::$changes_in_file);

            self::$log->Wr(sprintf("\tScan all directory: %d.",       self::$directorycount));
            self::$log->Wr(sprintf("\tScan all file: %d.",            self::$filecount));
            self::$log->Wr(sprintf("\tChanged files: %d.",            self::$filechanged));
            self::$log->Wr(sprintf("\tMade changes in files: %d.",    self::$changes_in_file));
        }


} // ------------------------------------ end class myChangeData -----------------------------------------------



// --------------------------------- MAIN Function --------------------------------------------- //
// --------------------------------------------------------------------------------------------- //


    printf("Start script !Change Data in Folder from file: %s\n", __FILE__);

    //load config parameters
    require_once 'config.php';
    $log = new myLogSubsystem();
    $work = new myChangeData();
    $work->myScanDirectory(START_FOLDER);
    $work->myOutputStatistic();
    $log->Closelog();
    printf("End script !Change Data in Folder!!!\n" );


// ------------------------ End of file ChandeInFile.php -------------------------------------------------
