/*
Script handlig PAYU recurring payments with credit card
$('.pms-credit-card-information').show();
              $('.pms-billing-details').show();
$pms_checked_paygate
$pms_checked_paygate.data('recurring') != 'undefined'
*/

jQuery( function($) {
  var  paygate_selector = 'input.pms_pay_gate';
  /**
   * Set checked payment gateway when clicking on a payment gateway radio
   *
   */
  jQuery( document ).on( 'click', paygate_selector, function() {

      if( jQuery(this).is(':checked') )
          $pms_checked_paygate = jQuery(this);

      let is_payu = false;
      if($pms_checked_paygate.val().includes('payu')){
        is_payu = true;
      }

      if( is_payu &&  $pms_checked_paygate.data('recurring') >= 1 ) {
        //Hide pms_confirm_retry_payment_subscription
        //and pms_new_subscription
      } else {
        //Show pms_confirm_retry_payment_subscription
        //and pms_new_subscription
      }

  });

  $(document).ready( function() {
    let is_payu = false;
    $(paygate_selector).each(function( index ) {
      ///console.log( index + ": " + this.value );
      if(this.value.includes('payu')){
        is_payu = true;
      }
    });
    //console.log( "payu ready!" );
    // console.log( JSON.stringify($pms_checked_subscription) + " " + JSON.stringify($pms_checked_paygate) );
    //if($pms_checked_paygate.val() == 'payu' && $pms_checked_subscription.data('recurring') >= 1 && $pms_checked_paygate.data('recurring') >= 1 ){
    if(is_payu){
      var $pms_paygates_inner = jQuery( '#pms-paygates-inner' );
      if ( $( "#payu-recurring" ).length ) {
        //Already appened
      } else {
        //Append
        var recurring_payu_form = get_recurring_payu_form();
        $pms_paygates_inner.append('<style>'+get_payu_css()+'</style>');
        $pms_paygates_inner.append('<div id="payu-recurring" class="pms-credit-card-information">' + recurring_payu_form + '</div>');
      }
    }
  });

  function get_payu_css(){
    var ret_val = `
      #payu-card-containter .container * {
    	font-family: Arial, Helvetica, sans-serif;
    	font-size: 14px;
    	color: #ffffff;
    }

    #payu-card-containter .container {
    	text-align: center;
    	width: 420px;
    	margin: 20px auto 10px;
    	display: block;
    	border-radius: 5px;
    	box-sizing: border-box;
    }

    #payu-card-containter .card-container {
    	width: 100%;
    	margin: 0 auto;
    	border-radius: 6px;
    	padding: 10px;
    	background: rgb(2,0,60);
    	text-align: left;
    	box-sizing: border-box;
    }

    #payu-card-containter .card-container aside {
    	padding-bottom: 6px;
    }

    #payu-card-containter .payu-card-form {
    	background-color: #ffffff;
    	padding: 5px;
    	border-radius: 4px;
    }

    #payu-card-containter .card-details {
    	clear: both;
    	overflow: auto;
    	margin-top: 10px;
    }

    #payu-card-containter .card-details .expiration {
    	width: 50%;
    	float: left;
    	padding-right: 5%;
    }

    #payu-card-containter .card-details .cvv {
    	width: 45%;
    	float: left;
    }

    #payu-card-containter button {
    	border: none;
    	background: #438F29;
    	padding: 8px 15px;
    	margin: 10px auto;
    	cursor: pointer;
    }

    #payu-card-containter .response-success {
    	color: #438F29;
    }

    #payu-card-containter .response-error {
    	color: #990000;
    }`;
    return ret_val;
  }

  function get_payu_js(){
    var src = `
    var optionsForms = {
        cardIcon: true,
        style: {
            basic: {
                fontSize: '24px'
            }
        },
        placeholder: {
            number: '',
            date: 'MM/YY',
            cvv: ''
        },
        lang: 'pl'
    }

    var renderError = function(element, errors) {
        element.className = 'response-error';
        var messages = [];
        errors.forEach(function(error) {
            messages.push(error.message);
        });
        element.innerText = messages.join(', ');
    };

    var renderSuccess = function(element, msg) {
        element.className = 'response-success';
        element.innerText = msg;
    };

    //inicjalizacja SDK poprzez podanie POS ID oraz utworzenie obiektu secureForms
    var payuSdkForms = PayU($pms_checked_paygate.data('pos_id'));
    var secureForms = payuSdkForms.secureForms();

    //utworzenie formularzy podając ich typ oraz opcje
    var cardNumber = secureForms.add('number', optionsForms);
    var cardDate = secureForms.add('date', optionsForms);
    var cardCvv = secureForms.add('cvv', optionsForms);

    //renderowanie formularzy
    cardNumber.render('#payu-card-number');
    cardDate.render('#payu-card-date');
    cardCvv.render('#payu-card-cvv');

    var tokenizeButton = document.getElementById('tokenizeButton');
    var responseElement = document.getElementById('responseTokenize');

    tokenizeButton.addEventListener('click', function() {
        responseElement.innerText = '';

        try {
            //tokenizacja karty (komunikacja z serwerem PayU)
            payuSdkForms.tokenize(SINGLE).then(function(result) { // przykład dla tokenu typu SINGLE
                result.status === 'SUCCESS'
                    ? renderSuccess(responseElement, result.body.token) //tutaj wstaw przekazanie tokena do back-endu
                    : renderError(responseElement, result.error.messages); //sprawdź typ błędu oraz komunikaty i wyświetl odpowiednią informację użytkownikowi
            });
        } catch(e) {
            console.log(e); // błędy techniczne
        }
    });`;
    var script = document.createElement("script");
        script.type = "text/javascript";
        script.innerHTML = src;
        document.getElementById('pms-paygates-inner').appendChild(script);
    //return src;
  }

  function get_recurring_payu_form(){
    var sdk_script = document.createElement("script");
        sdk_script.type = "text/javascript";
        //TODO: Chanege to production after tests
        sdk_script.src = "https://secure.snd.payu.com/javascript/sdk";
        //sdk_script.src = "https://secure.payu.com/javascript/sdk";
        sdk_script.onload = function() {
          //console.log("Script "+sdk_script.src+" loaded and ready");
          get_payu_js();
        };

        document.getElementById('pms-paygates-inner').appendChild(sdk_script);
        //<script type="text/javascript" src="https://merch-prod.snd.payu.com/javascript/sdk"></script>
    var html_form = `
            <div id="payu-card-containter">
            <section class="container">
                <div class="card-container">
                    <aside>Numer Karty</aside>
                    <div class="payu-card-form" id="payu-card-number"></div>

                    <div class="card-details clearfix">
                        <div class="expiration">
                            <aside>Ważna do</aside>
                            <div class="payu-card-form" id="payu-card-date"></div>
                        </div>

                        <div class="cvv">
                            <aside>CVV</aside>
                            <div class="payu-card-form" id="payu-card-cvv"></div>
                        </div>
                    </div>
                </div>
                <button id="tokenizeButton">Tokenizuj</button>

                <div id="responseTokenize"></div>
            </section>
            </div>`;
      return html_form;
  }

});
