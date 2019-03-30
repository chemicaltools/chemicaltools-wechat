var chemicaltoolsbot = require('chemicaltools-bot')
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
    if (message.MsgType === 'device_text') {
        res.reply(chemicaltoolsbot(message.Content, 'zh'))
    else if (message.MsgType === 'device_event') {
        res.reply(chemicaltoolsbot('help', 'zh'))
    }
}))

module.exports = app
