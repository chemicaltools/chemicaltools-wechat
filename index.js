var chemicaltoolsbot = require('./chemicaltoolsbot')
var wechat = require('wechat');
var express = require('express')
var app = express()
var config = {
    token: 'zengjinzhe',
    appid: 'wx0cd8b5047421a3aa',
    encodingAESKey: 'HQGUNPgz1fogojffKg0KDl6eW5UMEsdlhwGo0CdFWQj',
    checkSignature: true
  };
app.use(express.query());
app.use('/', wechat(config, function (req, res, next) {
    var message = req.weixin;
    res.reply(chemicaltoolsbot.reply(message.Content))
}))

module.exports = app