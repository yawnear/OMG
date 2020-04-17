/**
* 在Safari和IE8上执行 new Date('2017-12-8 11:36:45'); 会得到Invalid Date
* 本函数重写默认的Date函数，以解决其在Safari，IE8上的bug
*/
Date = function (Date) {
  MyDate.prototype = Date.prototype;
  return MyDate;

  function MyDate() {
    // 当只有一个参数并且参数类型是字符串时，把字符串中的-替换为/
    if (arguments.length === 1) {
      let arg = arguments[0];
      if (Object.prototype.toString.call(arg) === '[object String]' && arg.indexOf('T') === -1) {
        arguments[0] = arg.replace(/-/g, "/");
        // console.log(arguments[0]);
      }
    }
    let bind = Function.bind;
    let unbind = bind.bind(bind);
    return new (unbind(Date, null).apply(null, arguments));
  }
}(Date);

function httpPost(URL, PARAMS) {
	var iForm = document.createElement("form");
	iForm.action = URL;
	iForm.method = "post";
	iForm.style.display = "none";
	for (var x in PARAMS) {
		var opt = document.createElement("textarea");
		opt.name = x;
		opt.value = PARAMS[x];
		iForm.appendChild(opt);
	}
    document.body.appendChild(iForm);
	iForm.submit();
	return iForm;
}