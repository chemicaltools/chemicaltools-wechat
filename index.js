var chemicaltoolsbot = require('chemicaltoolsbot')
var wechat = require('wechat');
var config = {
    token: 'zengjinzhe',
    appid: 'wx0cd8b5047421a3aa',
    encodingAESKey: 'HQGUNPgz1fogojffKg0KDl6eW5UMEsdlhwGo0CdFWQj',
    checkSignature: false
  };
app.use(express.query());
app.use('/wechat', wechat(config).text(function (message, req, res, next) {
    res.reply(chemicaltoolsbot.reply(message))
}))
