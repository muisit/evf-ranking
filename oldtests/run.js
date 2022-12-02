var path = require('path');
var fs=require('fs');

var verbose=false;
var namedtests=[];
var nextisnamed=false;
process.argv.map((a) => {
    if(nextisnamed) {
        namedtests.push(a);
        nextisnamed=false;
    }
    else {
        if(a == "-v" || a == "--verbose") verbose=true;
        if(a == "-s" || a == "--silent") verbose=false;
        if(a == "-n" || a == "--named") nextisname=true;
    }
});

// find all tests in this directory
var basepath="./tests";
if(fs.existsSync(basepath)) {
    var files=fs.readdir(basepath, function(err, files) {
        var alltests=[];
        if(err) console.log(err);
        if(files && files.length) {
            files.map((fl) => {
                var filename=path.join(basepath,fl);
                var stat = fs.lstatSync(filename);
                if (!stat.isDirectory() && /\.js$/.test(filename)) {
                    console.log("found test ",filename);              
                    alltests.push(filename);
                }
            });
            runalltests(alltests);
        }    
    });
}

function runalltests(alltests) {

    alltests.sort();

    var numtests=0;
    var success=0;
    var fails=0;
    alltests.map((tst) => {
        var tstmod = require('./' + tst);
        var name =tstmod.name;
        if(tstmod.run && !namedtests.length || namedtests.includes(name)) {
            console.log("Running tests for ",name);
            var results = tstmod.run();
            if(results) {
                console.log("Tests: ",results.count," Success:" ,results.success, " Fails: ", results.fails);
                success+=results.success;
                fails+=results.fails;
                numtests+=results.count;
            }
        }
    });

    console.log("End of testing.");
    console.log("Total tests: ",numtests);
    console.log("Success:", success);
    console.log("Fails: ",fails);

    if(fails > 0) {
        process.exit(1);
    }
    else {
        process.exit(0);
    }
}

