const chemicaltools = require('chemicaltools')
const format = require('string-format')
format.extend(String.prototype, {})

var reply = function (input) {
    s = input.split(' ')
    if (s.length == 1) {
        if (input.toLowerCase() == "help" || input.toLowerCase() == "h" || input == "帮助") return help
        if (input.toLowerCase() == "element" || input == "元素") return anwserElementTable()
        result = chemicaltools.searchElement(input)
        if (result) return anwserElement(result)
        result = chemicaltools.calculateMass(input)
        if (result) return anwserMass(result)
        return "输入错误！"
    } else {
        if (s[0] == "HA" || s[0] == "BOH") return anwserAcid(s)
        if (s[0] == "p" || s[0] == "V" || s[0] == "n" || s[0] == "T") return anwserGas(s)
        return anwserDeviation(s)
    }

}

var help = `化学e+支持以下功能：
1.元素查询
输入元素名称/符号/原子序数/IUPAC名查询元素，输入“元素”查看所有元素。
示例：72
示例：Hafnium
2.质量计算
输入化学式计算分子量。
示例：(NH4)6Mo7O24
3.酸碱计算
输入HA（或BOH） 分析浓度 pKa（或pKb）计算溶液成分。
示例：HA 0.1 2.148 7.198 12.319
4.气体计算
输入未知量（p，V，n，T），并依次输入p，V，n，T中的已知量，即可计算未知量。
示例：n 101 1 298
5.偏差计算
输入一组数据计算其偏差（用空格间隔）。
示例：0.3414 0.3423 0.3407`

var anwserElement = function (info) {
    var outputinfo = { "元素名称": info.name, "元素符号": info.symbol, "IUPAC名": info.iupac, "原子序数": info.number, "相对原子质量": info.mass, "元素名称含义": info.origin }
    var output = ''
    for (var x in outputinfo) {
        output += "{0}：{1}\n".format(x, outputinfo[x])
    }
    output += "\n<a href='https://en.wikipedia.org/wiki/{1]'>访问维基百科</a>".format(info.name, info.iupac)
    return output
}

var anwserElementTable = function () {
    var output = ''
    for (var info in chemicaltools.elementinfo) {
        output += "{0}.{1}{2} {3} {4}".format(info.number, info.name, info.symbol, info.iupac, info.mass)
    }
    return output
}

var anwserMass = function (result) {
    var output = "{0}\n相对分子质量={1}".format(result.name, parseFloat(result.mass).toFixed(2))
    for (var i in result.peratom) {
        output += "\n{0}（符号：{2}），{3}个原子，原子量为{4}，质量分数为{5}%；".format(result.peratom[i].name, result.peratom[i].iupac, result.peratom[i].symbol, result.peratom[i].atomnumber, parseFloat(result.peratom[i].mass).toFixed(2), parseFloat(result.peratom[i].massper).toFixed(2))
    }
    return output.substring(0, output.length - 1) + "。"
}

var anwserAcid = function (s) {
    AorB = (s[0] == "HA" ? true : false)
    var result = chemicaltools.calculateAcid(parseFloat(s[1]), s.slice(2).map(parseFloat), AorB)
    var output = "{0}, c={1}mol/L, ".format(s[0], s[1])
    var i = 1;
    s.slice(2).forEach(function (pKa) {
        output += "pK{0}{1}={2}, ".format((AorB ? "a" : "b"), (s.slice(2).length > 1 ? "{0}".format(i++) : ''), pKa)
    });
    output += "\n溶液的pH为{0}.".format(result.pH.toFixed(2))
    result.ion.forEach(function (ion) {
        output += "\nc({0})={1}mol/L,".format(ion.name, ion.c.toExponential(2))
    })
    output = output.substring(0, output.length - 1) + "."
    return output
}

var anwserGas = function (s) {
    keys = ["p", "V", "n", "T"]
    input = { p: null, V: null, n: null, T: null }
    unit = { p: "kPa", V: "L", n: "mol", T: "K" }
    var i = 1, output = ''
    for (var key in input) {
        input[key] = (s[0] == key ? null : s[i++])
    }
    result = chemicaltools.calculateGas(...keys.map(function (x) {
        return input[x]
    }))
    for (var key in result) {
        if (key != s[0]) output += "{0}={1}{2}, ".format(key, result[key], unit[key])
    }
    output += "计算得{0}={1}{2}".format(s[0], result[s[0]], unit[s[0]])
    return output
}

var anwserDeviation = function (x) {
    var numnum = Infinity, pointnum = Infinity
    x.forEach(function (xi) {
        var len = xi.length
        var pointlen = 0
        if (xi.substr(0, 1) == "-") len--
        if (xi.indexOf(".") >= 0) {
            len--
            var pointlen = len - xi.indexOf(".")
            if (Math.abs(parseFloat(xi)) < 1) {
                var zeronum = Math.floor(Math.log((Math.abs(parseFloat(xi)))) / Math.LN10)
                len += zeronum
            }
        }
        numnum = Math.min(numnum, len)
        pointnum = Math.min(pointnum, pointlen)
    });
    result = chemicaltools.calculateDeviation(x.map(parseFloat))
    var outputinfo = [
        { name: "您输入的数据", value: x.join(", ") },
        { name: "平均数", value: result.average.toFixed(pointnum) },
        { name: "平均偏差", value: result.average_deviation.toFixed(pointnum) },
        { name: "相对平均偏差", value: (result.relative_average_deviation * 1000).toExponential(numnum - 1) + "‰" },
        { name: "标准偏差", value: result.standard_deviation.toExponential(numnum - 1) },
        { name: "相对标准偏差", value: (result.relative_standard_deviation * 1000).toExponential(numnum - 1) + "‰" },
    ]
    var output = ''
    outputinfo.forEach(function (info) {
        output += "{0}： {1}\n".format(info.name, info.value)
    });
    return output
}

exports.reply = reply
