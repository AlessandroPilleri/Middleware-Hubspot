var fs = require('fs');
var request = require('request');
var express = require('express');
var app = express();

console.log('Server started');
app.listen(80);
app.use(express.static('public'));

var accessToken = './public/accessToken.txt';
var tokenData = {};

url = 'https://app.hubspot.com/oauth/authorize?client_id=***********************&scope=contacts&redirect_uri=http://localhost/callback';
console.log('Open ' + url);

app.get('/callback', function (req, res) {
    console.log('code: ' + req.query.code);
    res.sendStatus(200)

    var queryData = {
        grant_type: 'authorization_code',
        client_id: '***********************',
        client_secret: '***********************',
        redirect_uri: 'http://localhost/callback',
        code: req.query.code
    }

    var query = '?grant_type=' + queryData.grant_type + '&client_id=' + queryData.client_id + '&client_secret=' + queryData.client_secret + '&redirect_uri=' + queryData.redirect_uri + '&code=' + queryData.code;

    var options = {
        url: 'https://api.hubapi.com/oauth/v1/token' + query,
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'charset': 'utf-8'
        }
    }
    request.post(options, function (err, response, body) {
        if (err) throw err;
        console.log(body)
        date = new Date().getTime();

        tokenData = JSON.parse(body);
        console.log('access token: ' + tokenData.access_token);
        console.log('refresh token: ' + tokenData.refresh_token);
        console.log('access token expires in ' + tokenData.expires_in);

        var expiresDate = date + (tokenData.expires_in * 1000);
        console.log('date: ' + new Date(Number(date)) + ' - ' + new Date(Number(date)).getTime())
        console.log('expires date: ' + new Date(Number(expiresDate)) + ' - ' + new Date(Number(expiresDate)).getTime())

        tokenData['expiresDate'] = expiresDate;
        console.log(JSON.stringify(tokenData));
    })
})

function refreshToken () {
    var queryData = {
        grant_type: 'refresh_token',
        client_id: '***********************',
        client_secret: '***********************',
        redirect_uri: 'http://localhost/',
        refresh_token: tokenData.refresh_token
    }

    var query = '?grant_type=' + queryData.grant_type + '&client_id=' + queryData.client_id + '&client_secret=' + queryData.client_secret + '&redirect_uri=' + queryData.redirect_uri + '&refresh_token=' + queryData.refresh_token;

    var options = {
        url: 'http://api.hubapi.com/oauth/v1/token' + query,
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'charset': 'utf-8'
        }
    }
    request.post(options, function (err, response, body) {
        if (err) throw err;
        console.log(body);
        var data = JSON.parse(body);
        tokenData.refresh_token = data.refresh_token;
        tokenData.access_token = data.access_token;
        tokenData.expires_in = data.expires_in;
        tokenData.expiresDate = new Date().getTime() + (data.expires_in * 1000);
    })
}

app.get('/refreshtoken', function (req, res) {
    var date1 = new Date();
    var date2 = new Date(tokenData.expiresDate)
    console.log('refresh token: ' + date1 > date2)
    if (date1 > date2) {
        refreshToken();
    }
    fs.writeFileSync(accessToken, tokenData.access_token);
    res.sendStatus(200)
})
