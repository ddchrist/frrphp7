<?php
include("login_headerscript.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>


</head>

<body>
<h1>Documentation COOPANS.COM/FRR</h1>
FRR former(Frankrigsrejser.dk) consist of the following parts/systems:
<ol>
<li>Login - handling of the login to coopans.com/frr</li>
<li>Search PCRs - a tool to mainly visualise Thales ClearQuest database.</li>
<li>Manage Projects - adminstrates workpackages and resources in the COOPANS project and visualise these on a monthly basis prepared for Business Warehouse.</li>
<li>Manage Checklist - a tool to adminstrate the COOPANS common checklist.</li>
</ol>
Further there are some minor office helping tools "Map subjects" and a statistic usage tool for "Search PCRs". These will not be documented by are located in the following files.
<ul>
<li>office_make_test_tables.php, office_download_initials.php</li>
<li>admin_stat.php</li>
</ul> 
In the following, the files making up these tools will be described on a high level. To understand the context of the files, use the tool and see whereto links points. Then open these files and examine for further clarification.

<h2>Login</h2>
<table>
<tr><td>index.html</td><td>First page indexed by the webserver. It request for login details and executes login_check.php</td></tr>
<tr><td>login_check.php</td><td>Check login details. If correct (users are fetched from mongodb  admin/members (database/collection), a session is initiated and login_initial_startup.php is executed.</td></tr>
<tr><td>login_headerscript.php</td><td>Included in the first lines of more or less all the php scripts to authenticate the user trying to access the script</td></tr>
<tr><td>login_initial_startup.php</td><td>Dependent on previous usage of tools on frankrigsrejser, the script redirects to what was used last time. Default is "Search PCRs"</td></tr>
<tr><td>login_success.php</td><td>Not used anymore</td></tr>
</table>


<h2>Search PCRs</h2>
Search PCRs contains the following subfunctionalities:
<ol>
<li>PCR Search</li>
<li>IRF New</li>
<li>IRF Search</li>
<li>Risk Search</li>
<li>Favorites</li>
<li>Admin</li>
<li>Reports</li>
</ol>

<h3>Adminstrative functionalites</h3>
<table border="1">
<tr><td>admin_adduser.php</td><td>Allow anybody with a login to add another user. Somewhat misplaced under Search PCRs. Mongodb admin/members</td></tr>
<tr><td>admin_excel_import.php</td><td>Makes it possible to import an Excel table directly into the database and also encrypt fields. A hard coded password is necessary to execute this function</td></tr>
<tr><td>admin_add_favorite.php</td><td>Add given PCR to the users favorites list with possible comments and deadline. Mongodb testdb/Favorites</td></tr>
<tr><td>admin_functions.php</td><td>Get and store global variables in to mongodb admin/variables. Also count up search statistics on users</td></tr>
<tr><td>admin_main.php</td><td>1) Index to the different admin functions.<br>2) Delete keys in specified mongodb database/collection.<br>3) Copy mongodb testdb/pcr to testdb/oldpcr. This is intended for the move from GAIA DB to ClearQuestDB in order to have a backup of the old GAIA DB in oldpcr that can be moved into the pcr database after a reload of a new ClearQuest database.<br>4) Get file name of ClearCase attachments into testdb/pcr</td></tr>
<tr><td>admin_print_pcrs.php</td><td>A flexible drop box where PCR numbers can be placed or listed and then the system will fetch selected keys of these PCRs from the ClearQuest DB.</td></tr>
<tr><td>admin_programs.html</td><td>The menu entry (M) which lists and access the tools on frankrigsrejser.dk</td></tr>
<tr><td>admin_stat.php</td><td>List usage statistics on "Search PCRs"</td></tr>
<tr><td>admin_uploadclearquestpcrs.php</td><td>Uploads the ClearCase database (not fully implemented yet)</td></tr>
<tr><td>admin_uploadgaiapcrs.php</td><td>Upload the GAIA database. No longer used as GAIA is obsolete</td></tr>
<tr><td>admin_uploadpcrs.php</td><td>Upload Mermaid xls dump if GAIA. No longer used as obsolete</td></tr>
<tr><td>attachments</td><td>Directory where Thales PCR attachments are stored. Each PCR that has an attachment gets a sub directory named with the ClearCase PCR name e.g. COMP00098791. In this directory attachments are stored as plain files in different formats. The linking between attahments and PCRs are done by a funtion in admin_main.php</td></tr>


<tr><td>headerline.html</td><td>Top index line with links to Search PCR functionalities</td></tr>
<tr><td>pcr_severity_priority.php, pcr_update_priority_severity.php</td><td>Used for ANSPs to set their priority and severity of individual PCRs. This has been disconnected as noone is using it. Mongodb testdb/prisev</td></tr>
<tr><td>printGAIA.php, print.php</td><td>Used to visualise respectively PCRs from GAIA and ClearQuest on the screen</td></tr>
<tr><td>gaia, gaia2</td><td>Directories used for downloading Thales GAIA PCRs. As GAIA no longer is delivered, these directories will eventually be removed</td></tr>
<tr><td>PrepareGaiaDataB1.sh, PrepareGaiaDataB2.sh, vmsplat</td><td>Linux scripts used to unpack GAIA dumps received from Thales. Not likely to be used anymore.</td></tr>
<tr><td>risk_list.php, risk_search.php, risk_view.php</td><td>Scripts to write, search and list Risk assestments connected to individual PCRs. Presently this functionality is disconnected adding new risk assestments. Can still be searched and viewed</td></tr>
<tr><td>track_pcr.php</td><td>A tool/script to administrate the phases during which relevant PCRs are found, discussed, requested CPPG/Thales and finally tested and closed</td></tr>
</table>


<h3>PCR Search</h3>
<table border="1">
<tr><td>search_input.php</td><td>Main type to inoput criterias for seaching PCRs.</td></tr>
<tr><td>search_list.php</td><td>If more than one PCR matches a criteria, they are listed</td></tr>
<tr><td>search_input_response.php</td><td>Response script to javascript routines that handle GAIA B1 and B2 differentiation. No longer used with ClearQuest</td></tr>
<tr><td>search_pcr_pr_irf_response.php</td><td>Response script to javascript routines that handles setting of DetectedRelease so it is not necessary to submit changes i.e. autoupdated</td></tr>
<tr><td>search_preselected_filter.php</td><td>Handling the adminstration of "Combined PCR Search" i.e. Get Preselected Filters, Delete and Save, stored in mongodb testdb/PreselectedFilters</td></tr>
<tr><td>search_fulltext.php</td><td>Handling and adminstration of Full Text Search</td></tr>
<tr><td>search_ImportClearQuestTextDump.php</td><td>Imports the ClearQuest text dumps formated by Vincent using Thales CLearQuest Tool.</td></tr>
<tr><td>search_info_keys.html</td><td>Explanation to ClearQuest keys "Detection Phase" and "Origin"</td></tr>
<tr><td>search_reports.php</td><td>Page indexing different reports listing PCR statistics</td></tr>
<tr><td>search_report_accumulated.php</td><td>Accumulated PCR report</td></tr>
<tr><td>search_report_phases.php</td><td>Report on PCR phases</td></tr>
</table>

<h3>IRF Search / IRF New</h3>
Handling of IRF are currently not done using this tool.
<table border="1">
<tr><td>irf_input.php</td><td>Page to input IRF data</td></tr>
<tr><td>irf_insert_db.php</td><td>Insert IRF into mongodb testdb/irf. Each ANSP have their own IRF number counter mongodb testdb/irfcounters</td></tr>
<tr><td>irf_search.php</td><td>Search in th elist of IRFs dependent on criterias given on this page</td></tr>
<tr><td>irf_list.php</td><td>If more than one IRF is found, they are listed here</td></tr>
<tr><td>irf_view.php</td><td>The actual view of a selected IRF</td></tr>
<tr><td>irf_select_upload.php</td><td>A form from where file to be uploaded can be accessed, downloaded and deleted</td></tr>
<tr><td>irf_upload_files.php</td><td>Upload a selected file to mongodb using GridFS</td></tr>
<tr><td>irf_download.php</td><td>Download selected file</td></tr>
</table>

<h2>Manage Projects</h2>
<table border="1">
<tr><td>psp_elements.html</td><td>Regular maintained file with information on relevant PSP elements in relation to COOPANS builds</td></tr>
<tr><td>class_lib.php</td><td>General classes used by both "Manage Project" and "Manage Checklist". It is used to handle memory allocation of tables in mongodb, manage tables with input and deletion of rows, mish. calculations of table data, javascript calendar handling, date format validations, user rights, storing of data on mongodb database/admin collection, find super levels of WP numbers, validate WP numbers, enhance writing of html by storing in more reader friendly format, calulation of sums of manhours/days and many more things. </td></tr>
<tr><td>res_admin.php</td><td>Links to different adminstrative functions:<br>1) User rights<br>2)Define roles<br>3)Define Teams<br>4)List Teams<br>5)Create list of WP allocation in relation to sub dates<br>6) Compare WPs between Cockpit and LastQ Cockpit<br>7) Match Subjects to Units (a list of mappings is maintained)<br>8) Update dates in all WPs<br>9) Change Resource Owner of all resources<br>10) Make sure that all roles have a matching resource owner as given in table 'Define Roles'<br>11) Change Role of a list of resources<br>12) Change name of expected resource<br>13) Managing holiday inputs</td></tr>
<tr><td>res_admin_project.php</td><td>Create New Projects - this is never tested and might not work</td></tr>
<tr><td>res_admin_uploadSAP.php</td><td>Upload SAP Resource dump, Update Cockpit</td></tr>
<tr><td>res_cockpit2.php</td><td>Experimential - to be deleted</td></tr>
<tr><td>res_cockpit.php</td><td>Handling the Cockpit view with selections.<br>1) Calculation of resource usage from WPs<br>2) calculationsBackup/Restore/Copy Cockpit to current and last quarter<br>3) Manage Current and This Quarter views<br>4) Make detailed resource reports (List Subjects)<br>5) Calculate Business Warehouse sums in relation to SAP "AktArt"</td></tr>
<tr><td>res_export.php</td><td>Export Work Pacakages to .csv format</td></tr>
<tr><td>res_headerline.html</td><td>Top line links to handle functions within "Manage Project"</td></tr>
<tr><td>res_make_ms_project.php</td><td>Export Work Pacakages to MS Project Import (only works with UK version)</td></tr>
<tr><td>res_manage_input_table.php</td><td>Manage actual input the an individual selected Work Package and post calculations</td></tr>
<tr><td>res_manage_wps.php</td><td>Manage Work Packages i.e. viewing, numbering, renameing, adding, deletion, copying, tags</td></tr>
<tr><td>res_register_wps.php</td><td>Makes possible to assign a subject as allocated resource to a work package without having to perform login. Use of predefined tag "#links" in filter will generate a list of links to do this from the Work Package view.</td></tr>
</table>

<h2>Manage Checklist</h2>
<table border="1">
<tr><td>testchecks</td><td>Directory where .zip dumps of testchecks are stored. These dumps are genrated with Girafe or the Excel test execution tool</td></tr>
<tr><td>class_lib.php</td><td>Same as / see under "Manage Projects"</td></tr>
<tr><td>chk_admin.php</td><td>Links to different adminstrative functions:<br>1) User rights<br>2)Define roles<br>3)Define Teams<br>4)List Teams<br>5) Clone Teams<br>6) Correct ACG MASTER - experimential tool to covert roles used in MASTER. WOUld require reprogramming for other usages<br>7) Correct Release number of TestChecks<br>8) List and administrate DPR settings/values used in TestChecks</td></tr>
<tr><td>chk_admin_project.php</td><td>Not used for the checklist</td></tr>
<tr><td>chk_convert.php</td><td>Converts the first MASTER checklist to something usefull for this tool. Must not be used anymore</td></tr>
<tr><td>chk_export.php</td><td>Export the ckecklist to formats .csv, .xls. xml</td></tr>
<tr><td>chk_headerline.html</td><td>Top line links to handle functions within "Manage Checklist"</td></tr>
<tr><td>chk_import_testchecks_excel.php</td><td>Import TestChecks from Excel .xls file (Excel 97-2003 format)</td></tr>
<tr><td>chk_manage_input_table.php</td><td>Manage actual input the an individual selected Test Cases and post calculations</td></tr>
<tr><td>chk_manage_wps.php</td><td>Manage Test Cases i.e. viewing, numbering, renameing, adding, deletion, copying</td></tr>
<tr><td>chk_manage_wps_response.php</td><td>Script to handle feedback to Javascript for handling easy input of RoleTeams by just clicking/chec king</td></tr>
<tr><td>chk_view_results.php</td><td>Show Test Checks results from all contributers. Upload Test Check files zipped</td></tr>
</table>

<h2>Misch. support files</h2>
<table border="1">
<tr><td>Chart.js, Chart.min.js</td><td>Javascript function used to draw charts in different reports, see link in headerline "Reports"</td></tr>
<tr><td>datepicker.min.js, datepicker.min..css</td><td>Javascript that pop-up a selectable calendar. Used in description of work package description in Manage Projects. http://freqdec.github.io/datePicker/</td></tr>
<tr><td>copy2usb</td><td>A shell batch script that copy all necessary files for setting up frankrigsrejser to a given destination. The file also explains how to set-up the mongodb webserver and explains which rights must be given to directories. This is the way in to start or rebuild a new server.</td></tr>
<tr><td>moadmin.php</td><td>A MongoDB administration tool. Requires hardcoded password to access</td></tr>
<tr><td>excel_reader2.php, php-excel.class.php</td><td>Script/Classes to read an Excel .xls file into a variable</td></tr>
<tr><td>scrollfix.js</td><td>Javascript used to locate scroll position on html page when using the back functionality or in other way to return to a page visited before</td></tr>
<tr><td>select2download.php</td><td>List all keys of a given database/collection in the mongodb database and makes it possible to select what keys to be downloaded. Selections are memorised in mongodb admin/variables</td></tr>
<tr><td>downloadcsv.php</td><td>Download selected keys dependent on which format is selected e.g. csv, Excel, XML</td></tr>
<tr><td>Mongodb admin/InitialMappings</td><td>A collection of all Naviair employees (a dump from SAP) with the following keys (example - *=encrypted):
<pre>
  'Unit' => 'AC',
  'MAnr' => '405147',
  'Subject' => 'MER',
  '*Efternavn' => 'Bq7V/ZVKYAza1QegY0syCnb+244GRaQbj+meF7DHR38=',
  '*Fornavn' => 'wd2b9+gfVwls/VCopqaqpJ4GapORwNbfZAOCtbtHqUM=',
  'Delomr' => 'CH01',
  'Personaledelområde' => 'Chefgruppe ADM',
  'MAKrs' => 'A1',
  '*Medarbejderkreds' => 'EJeo7cidfUE77rGMVxmFVmiX5+2SjGX+V2v7w3bQImc=',
  '*Stilling' => 'jLYlPNopSkRxCmAIQtA5/1MmwakzdsiucyiYhKp/OSY=',
  'Cost#' => '5101',
  '*Omkostningssted' => '2PQwMZ8sBJzMiVRkm37wmU3uAKT3ksBjz+dhA9LiONI=',
  'AktArt' => 'LEDER2',
  'AfsOmkSted' => '5101',
  '*Afsender_omkostningssted_tekst' => 'j+0D4uHLfTMDiqMgS2oPe9r9UJsG96uSbaJK6vG8L6o=',
  '*OrgEnhed' => 'aU7ObiKMui0l1wsB+SO0K3rcicsYZ8R/thwMBpZnw9k=',
  '*Organisationsenhed' => '7YrxnTYHkE6lIwrKWh/mz+q97gCychSGYf8uvZzqOck=',
</pre></td></tr>
</table>
















</body>

</html>
