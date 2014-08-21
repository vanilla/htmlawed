var page = require('webpage').create(),
    fs = require('fs');

page.settings.XSSAuditingEnabled = true;
page.settings.javascriptEnabled= true;

//console.log(JSON.stringify(page.settings));

page.onAlert = function(msg) {
    console.log('ALERT: ' + msg);
};

console.log('Loading')
page.open('phantomjs.html', function() {
    console.log('Loaded');
    phantom.exit();
});