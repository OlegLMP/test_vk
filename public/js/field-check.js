/**
 * Проверка полей
 */
function FieldCheck(params)
{
    /**
     * Основные свойства
     */
	this.required = true;
    this.params = '';
    this.sign = '';
    this.actionUrl = '/check/check';
    this.input = false;

    this.jqCache = [];
    this.tmFilter = 0;
    this.canCache = '';
    this.lastVal = null;

    this.init = function(params)
    {
        this.parseParams(params);
        this.handle(this.input);
        
        // Если в локальных проверках участвуют другие поля, то вешаемся и на их события 
        if (typeof this.params == 'string' && this.params.length)	{
        	var params = JSONToObject(this.params);
        	if (typeof params == 'object') {
        		for(var k in params) {
        			if (params[k][0] == 'local') {
        				switch(params[k][1]) {
        					case 'inputEquals':
        						this.handle($($(this.input)[0].form).find('#' + params[k][2]));
        						break;
        				}
        			}
        		}
        	}
        }

    };
    
    this.handle = function(element)
    {
    	var calc = this;
        $(element).keyup(function (e) {
            if (e.keyCode == 13) {
                calc.doApplyFilter();
            } else {
                calc.applyFilter();
            }
        }).change(function () {
        	calc.applyFilter(200);
        }).click(function () {
        	calc.applyFilter(2000);
        });
    }

    this.parseParams = function(params)
    {
        if (params) {
            for (var name in params) {
                this[name] = params[name];
            }
        }
    };

    this.applyFilter = function(delay, refresh)
    {
        if (refresh) {
        	this.setStyle();
            this.canCache = 0;
        } else {
            this.canCache = 1;
        }
        if (this.tmFilter) {
            clearTimeout(this.tmFilter);
        }
        if (!delay) {
            delay = 5000;
        }
        this.tmFilter = setTimeout(this.doApplyFilter, delay, this);
    };
    
    this.setStyle = function(result, message)
    {
    	if (typeof result == 'undefined') {
    		className = 'Checking';
    	} else if (result) {
    		className = 'Ok';
    	} else {
    		className = 'Error';
    	}
    	if (typeof message == 'undefined') {
    		message = '';
    	}
    	$(this.input).removeClass('fieldOk');
    	$(this.input).removeClass('fieldError');
    	$(this.input).removeClass('fieldChecking');
    	$(this.input).addClass('field' + className);
    	$(this.input).attr('title', message);
    	
    	fieldCheckStatus = $(this.input).next();
    	if (fieldCheckStatus.attr('class') != 'fieldCheckStatus') {
    		$(this.input).after('<span class="fieldCheckStatus"></span>');
    		fieldCheckStatus = $(this.input).next();
    	}
    	fieldCheckStatus.empty()
    		.append($('<span class="fieldCheckStatus' + className + '"></span>')
    				.attr('title', message))
    		.append($('<span class="fieldCheckStatusText"></span>')
    				.text(message));
    }

    this.doApplyFilter = function(calc)
    {
        if (!(this instanceof FieldCheck)) {
            if (calc instanceof FieldCheck) {
                calc.doApplyFilter();
            }
            return;
        }

        if (this.tmFilter) {
            clearTimeout(this.tmFilter);
            this.tmFilter = 0;
        }
        
        var val = $(this.input).val();
         
        // Если значение не задано, то сразу выводим результат проверки 
        // в зависимости от того, обязательно ли заполнение поля
        if (! val) {
        	if (this.required) {
        		this.setStyle(false, 'Обязательно для заполнения');
        	} else {
        		this.setStyle(true);
        	}
        	return;
        }
        
        // Проводим локальные проверки без обращения к серверу
        var cacheVal = val;
        if (typeof this.params == 'string' && this.params.length)	{
        	var params = JSONToObject(this.params);
        	if (typeof params == 'object') {
        		for(var k in params) {
        			if (params[k][0] == 'local') {
        				switch(params[k][1]) {
        					case 'inputEquals':
        						val2 = $($(this.input)[0].form).find('#' + params[k][2]).val();
        						cacheVal += ' / ' + val2;
        						if (val != val2) {
        							this.onResult({result : false, message : 'Введённые пароли не совпадают'});
        							this.lastVal = ''; //Сбрасываем кэш
        							return;
        						}
        						break;
        				}
        			}
        		}
        	}
        }
        
        // Если значение совпадает с кэшем от предыдущего запроса, то ничего не делаем
        if((this.lastVal == cacheVal) && this.canCache){
            return;
        }
        this.lastVal = cacheVal;
        
        // Если параметры проверки не заданы, то выводим Ok
        if (typeof this.params != 'string' || ! this.params.length)	{
        	this.setStyle(true);
        	return
        }
        
        // Устанавливаем крутилку
        this.setStyle();
        // И делаем запрос на сервер
        var calc = this;
        $.getJSON(this.actionUrl, {val : val, params : this.params, sign : this.sign}, function(data) {
            calc.onResult(data);
        });
    };

    this.onResult = function(data)
    {
        if(typeof data == 'object') {
        	this.setStyle(data.result, data.message);
        } else {
        	this.setStyle(false);
        }
    };
    
    this.init(params);
}

FieldCheck.initAll = function(container) {
	if (typeof container == 'undefined') {
		elements = $('[class*=fieldCheck]');
	} else {
		elements = $(container).find('[class*=fieldCheck]');
	}
	for (var i = 0; i < elements.length; i ++) {
		if (elements[i].fieldChecker) {
			continue;
		}
		elements[i].fieldChecker = new FieldCheck({
			input : elements[i],
			params : $(elements[i]).attr('params'),
			sign : $(elements[i]).attr('sign')
    	});
	}
};

$(function(){
	FieldCheck.initAll();
});

