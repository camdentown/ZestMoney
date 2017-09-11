/**
 *  code to update in view/frontend/web/js/view/payment/mentod-renderer/zestemi-method.js
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
 /*browser:true*/
 /*global define*/
 define(
    [
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'mage/url',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/action/redirect-on-success'
    ],
    function ($,Component,url,placeOrderAction,redirectOnSuccessAction) {
        'use strict';
        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                template: 'Zest_ZestMoney/payment/zestemi'
            },
            GetPricingQuote: function() {
                $.ajax({
                    url: window.checkoutConfig.payment.zestemi.QuoteUrl+'Pricing/quote?MerchantId='+window.checkoutConfig.payment.zestemi.ClientId+'&LoanAmount='+window.checkoutConfig.payment.zestemi.GrandTotal,
                    type: 'GET',
                    success: function(data){  
                        var zestMerchant = {};
                        zestMerchant.quotes = data.Quotes;
                        $('.zest-congrats-user h1').html("Pay Rs."+ (Number(data.DownpaymentAmount) + Number(data.ProcessingFee))+"/- &amp; now and convert your purchase into EMIs");
                        var bodyHtml = '';
                        var bodyHtml_result = '';
                        zestMerchant.quotes.forEach(function(quote){
                            bodyHtml = bodyHtml+'<th>'+quote.IntallmentCount+' months</th>';
                            bodyHtml_result = bodyHtml_result + '<td> Rs.'+quote.MonthlyInstallment+'</td>';  
                        });
                        bodyHtml = bodyHtml+'<th rowspan="2" class="zest-cell-1">Or Design your own EMI Plan</th>';
                        if($('table.zest-emi tbody tr').length == 0){
                            $('table.zest-emi tbody').append("<tr><th>Tenure</th>"+bodyHtml+"</tr>");
                            $('table.zest-emi tbody').append("<tr><th>EMI</th>"+bodyHtml_result+"</tr>");
                        }
                    },
                    error: function(data) { 
                        console.log(data); //or whatever second
                    }
                });
            },

            /** Returns send check to info */
            getMailingAddress: function() {
                //return window.checkoutConfig.payment.checkmo.mailingAddress;
                return window.checkoutConfig.payment.zestemi.mailingAddress;
            },

            getfaqurl: function() {
              //  return 'http://zestmoney.in/faq/';
              return window.checkoutConfig.payment.zestemi.faqurl;
          },

          getdesktop: function() {
               // return 'http://localhost/magento2sample/pub/static/frontend/Explorertheme/test/en_US/Zest_ZestMoney/images/01.png';
               return window.checkoutConfig.payment.zestemi.desktopimage;
           },

           getmobile: function() {
               // return 'http://localhost/magento2sample/pub/static/frontend/Explorertheme/test/en_US/Zest_ZestMoney/images/01.png';
               return window.checkoutConfig.payment.zestemi.mblimage;
           },


           afterPlaceOrder: function (data, event) {
               window.location.replace(url.build('zestmoney/zestpay/redirect'));
               
           }
           
       });
    }
    );
