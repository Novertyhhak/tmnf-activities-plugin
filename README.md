# TMNF activity plugin
A xaseco plugin for Trackmania Forever. Allows you to save timestamps of operators and later compare those together.

## Disclaimer
The code isn't the best, didn't think I would be sharing it!

## Usage
Save a timestamp *(see ```activity_savetodb``` below).* 
Come back after some time *(a week for example)* and save another timestamp.
Now compare those two *(see ```activity_compare``` below)*.
If you don't remember how you have named your timestamps use ```/activity_showcols```

## Instalation
1. Download the file
2. Put it into xaseco/plugins/
3. Add a line in xaseco/plugins.xml
4. Restart xaseco (/admin shutdown)

## Commands
```/activity_savetodb <name>``` - saves stats to the 'activity' table in a column named <name> I recommend naming those timestamps with YYYY_MM_DD format.

```/activity_showcols``` - returns all created timestamps (so basically column names) in chat

```/activity_compare <older> <newer>``` - compares an older timestamp <older> to a newer one <newer> and displays for every operator:
	*login
	*current average*
	*improved average since older timestamp*
	*time played since older timestamp*
	*all recorded finishes since older timestamp*
	*recorded visits since older timestamp*

```/activity_removecol <name>``` - removes a timestamp you specified as the arg

```/lastonops``` - an additional command, simply shows /laston for every operator in a window
