var chemicaltoolsbot = require('chemicaltoolsbot')
var wechat = require('wechat');
app.use(express.query());
app.use('/wechat', wechat('zengjinzhe').text(function (message, req, res, next) {
    res.reply(chemicaltoolsbot.reply(message))
})