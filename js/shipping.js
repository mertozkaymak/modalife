function volumetricCart(dataId, pId, qty, slug){

    let response;
    response = JSON.parse(localStorage.getItem("volumetricCart"));

    if(response !== null){

        let is;
        is = false;

        let counter;
        counter = 0;

        for (const key in response) {

            if(parseInt(response[key].id) === parseInt(pId)){
                is = key;
                break;
            }

            counter++;

        }

        if(is === false){
            response[counter] = { id: pId, slug: slug, dataId: dataId, qty: qty, weight: volumetricWeight, totalWeight: (qty * volumetricWeight) };
            localStorage.setItem("volumetricCart", JSON.stringify(response));
        }else{
            response[is].dataId = dataId;
            response[is].qty = parseFloat(response[is].qty) + parseFloat(qty);
            response[is].totalWeight = response[is].qty * response[is].weight;
            localStorage.setItem("volumetricCart", JSON.stringify(response));
        }

    }else{
        localStorage.setItem("volumetricCart", JSON.stringify([{ id: pId, slug: slug, dataId: dataId, qty: qty, weight: volumetricWeight, totalWeight: (qty * volumetricWeight) }]));
    }

}

(function($){

    let data;
    data = new Object();

    let complateShoppingTrigger;
    complateShoppingTrigger = false;

    let editAddressFormTrigger;
    editAddressFormTrigger = false;

    function number_format (number, decimals, dec_point, thousands_sep) {
        number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function (n, prec) {
                var k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
            };
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
        }
        return s.join(dec);
    }

    function activated(){

        let active;
        active = false;

        for (let index = 0; index < IdeaCart.items.length; index++) {

            if(IdeaCart.items[index].product.id === 17827){
                active = true;
                break;
            }

        }

        return active;

    }

    function editAddressForm(){

        let clone;
        clone = $("#shippingCountry").parent().parent().clone();

        clone.find("select option").remove();
        clone.find("select").attr("id", "shippingLocation2").attr("name", "location2").append("<option value>Eyalet</option>");
        clone.find("label").attr("for", "shippingLocation2").text("Eyalet");

        $("#shippingCountry").parent().parent().after($(clone));
        $("#shippingLocation").parent().parent().addClass("d-none");

        $("#shippingCountry").val($("#shippingCountry option:eq(0)").val()).trigger("change");

        if($("#address").val().indexOf("| Posta Kodu") !== -1){
            $("#address").val($("#address").val().split("| Posta Kodu")[0].trim()).trigger("change");
        }
        
    }

    function editAddressForm2(){

        let clone;
        clone = $("#country").parents(".form-group-binary:eq(0)").clone();

        clone.find("select option").remove();
        clone.find("select").attr("id", "location2").attr("name", "location2").append("<option value>Eyalet</option>");
        clone.find("select").removeAttr("autocomplete").removeAttr("data-selector").removeAttr("aria-invalid");
        clone.find("label").attr("for", "location2").text("Eyalet");

        $("#country").parents(".form-group-binary:eq(0)").after($(clone));
        $("#location").parents(".form-group-binary:eq(0)").addClass("d-none");

        $("#country").val($("#country option:eq(0)").val()).trigger("change");

        if($("[name='address']").val().indexOf("| Posta Kodu") !== -1){
            $("[name='address']").val($("[name='address']").val().split("| Posta Kodu")[0].trim()).trigger("change");
        }
        
    }

    function appendZipCode(){
        
        let clone;
        clone = $("#shippingSubLocation").parent().parent().clone();

        clone.find("input").attr("id", "zipCode").attr("name", "zipCode").attr("type", "number");
        clone.find("label").attr("for", "zipCode").text("Post kodu");

        $("#shippingSubLocation").parent().parent().before($(clone));
    }

    function appendZipCode2(){
        
        let clone;
        clone = $("#surname").parents(".form-group-binary:eq(0)").clone();

        clone.find("input").attr("id", "zipCode").attr("name", "zipCode").attr("type", "number");
        clone.find("input").removeAttr("autocomplete").removeAttr("value");
        clone.find("label").attr("for", "zipCode").text("Post kodu");

        $("#town").parents(".form-group-binary:eq(0)").before($(clone));
        $("#town").parents(".row:eq(0)").find(".col-md-4, .col-md-6").removeClass("col-md-4 col-md-6").addClass("col-md-3");
    }

    function listOfStates(code){

        let stateOptions;
        stateOptions = $("#shippingLocation2").find("option");

        for (let index = 0; index < stateOptions.length; index++) {
            
            if(index !== 0){
                $(stateOptions[index]).remove();
            }

        }


        for (let index = 0; index < data.countries.length; index++) {

            if(data.countries[index]["Country Code"] == code){

                for (const key in data.countries[index]["States"]) {
                    $("#shippingLocation2").append("<option value='" + data.countries[index]["States"][key]["Weight Price"] + "'>" + key + "</option>");
                }

            }

        }

    }

    function listOfStates2(code){

        let stateOptions;
        stateOptions = $("#location2").find("option");

        for (let index = 0; index < stateOptions.length; index++) {
            
            if(index !== 0){
                $(stateOptions[index]).remove();
            }

        }


        for (let index = 0; index < data.countries.length; index++) {

            if(data.countries[index]["Country Code"] == code){

                for (const key in data.countries[index]["States"]) {
                    $("#location2").append("<option value='" + data.countries[index]["States"][key]["Weight Price"] + "'>" + key + "</option>");
                }

            }

        }

    }

    function editCheckout2(){

        let dataCountryCode;
        dataCountryCode = $("#address-list [data-type='shipping-address-wrapper'] .address-block-header [data-type='shipping']").attr("data-country-code");

        taxAmount(dataCountryCode);

        let checkTown;
        checkTown = $("#address-list [data-type='shipping-address-wrapper'] .address-block-header [data-type='shipping'] p").text().split(" - ");
        checkTown = checkTown[2].trim();

        for (let index = 0; index < data.countries.length; index++) {

            if(data.countries[index]["Country Code"] == dataCountryCode){
                
                for (const key in data.countries[index]["States"]) {

                    if(key == checkTown){
                        cargoAmount(data.countries[index]["States"][key]["Weight Price"], data.countries[index]["Country Code"]);
                    }

                }

            }

        }

    }

    function editCheckout2WithTax(amount, total){

        if($("#cart-summary .cart-panel-amount-details #tax").length > 0){
            $("#cart-summary .cart-panel-amount-details #tax").remove();
        }

        if($("#cart-summary .cart-panel-amount-details #totalAmountWithTax").length > 0){
            $("#cart-summary .cart-panel-amount-details #totalAmountWithTax").remove();
        }

        let clone;
        clone = $("#cart-summary .cart-panel-amount-details .cart-panel-row:eq(0)").clone();

        clone.attr("id", "tax");
        clone.find("span:eq(0)").text("Vergi");
        clone.find("span:eq(1)").attr("data-amount", amount).text(number_format(amount, 2, ",", ".") + " EUR");

        $("#cart-summary .cart-panel-amount-details .cart-panel-row:eq(0)").after($(clone));

        let clone2;
        clone2 = $("#cart-summary .cart-panel-amount-details .cart-panel-row:eq(0)").clone();

        clone2.attr("id", "totalAmountWithTax");
        clone2.find("span:eq(0)").text("Vergi Dahil");
        clone2.find("span:eq(1)").attr("data-amount", total).text(number_format(total, 2, ",", ".") + " EUR");

        $("#cart-summary .cart-panel-amount-details .cart-panel-row:eq(1)").after($(clone2));
        $("#cart-summary .cart-panel-amount-details #cart-total-amount").attr("data-final-amount", total).text(number_format(total, 2, ",", ".") + " EUR");

    }

    function taxAmount(code){

        let subTotal;
        subTotal = IdeaCart.totalPrice;

        let hasTax;
        hasTax = false;

        for (let index = 0; index < data.countries.length; index++) {

            if(data.countries[index]["Country Code"] == code){

                let taxRate;
                taxRate = parseFloat(data.countries[index]["Tax"]);

                let taxAmount;
                taxAmount = Math.ceil((subTotal * taxRate) / 100);

                subTotal = subTotal + taxAmount;

                editCheckout2WithTax(taxAmount, subTotal);
                hasTax = true;

            }

        }

        if(hasTax === false){
            editCheckout2WithTax(0, 0);
        }

    }

    function checkMinCargoPrice(cargo, country){

        for (let index = 0; index < data.countries.length; index++) {

            if(data.countries[index]["Country Code"] == country){

                if(cargo < data.countries[index]["Min Price"]){

                    return data.countries[index]["Min Price"];

                }

            }

        }

        return cargo;

    }

    function cargoAmount(value, country){

        let response;
        response = parseFloat(localStorage.getItem("volumetricTotalWeight"));

        if(response !== null){
            
            let cargoAmount;
            cargoAmount = Math.ceil(value * response);

            cargoAmount = checkMinCargoPrice(cargoAmount, country);

            $("#cart-summary .cart-panel-amount-details .cart-panel-row:contains('Nakliye') span:eq(1)").attr("data-amount", cargoAmount.toFixed(2)).text(number_format(cargoAmount, 2, ",", ".") + " EUR");

            $("#checkout-cargo-details-content .contentbox-body .radio-custom").each(function(){

                if($(this).find("input").val() == data.cargoId){
                    $(this).find("input").attr("data-shipping-amount", cargoAmount);
                    $(this).find("label .cargo-right .label-price strong").text(number_format(cargoAmount, 2, ",", ".") + " EUR");
                    $(this).find("input")[0].checked = true;
                }

            });

            let total;
            total = parseFloat($("#cart-summary .cart-panel-amount-details #totalAmountWithTax span:eq(1)").attr("data-amount")) + cargoAmount;
            $("#cart-summary .cart-panel-amount-details #cart-total-amount").attr("data-final-amount", total.toFixed(2)).text(number_format(total, 2, ",", ".") + " EUR");
            
        }

    }

    function volumetricCart2(dataId, qty){

        let response;
        response = JSON.parse(localStorage.getItem("volumetricCart"));

        if(response !== null){

            let hasItem;
            hasItem = false;

            for (const key in response) {

                if(parseInt(response[key].dataId) === parseInt(dataId)){
                    hasItem = key;
                    break;
                }

            }

            if(hasItem !== false){
                response[hasItem].qty = qty;
                response[hasItem].totalWeight = response[hasItem].qty * response[hasItem].weight;
                localStorage.setItem("volumetricCart", JSON.stringify(response));
            }

        }

    }

    function volumetricCart3(dataId){

        let response;
        response = JSON.parse(localStorage.getItem("volumetricCart"));

        if(response !== null){

            let response2;
            response2 = new Object();

            let counter;
            counter = 0;

            for (const key in response) {

                if(parseInt(response[key].dataId) == parseInt(dataId)){

                    delete response[key];

                    for (const key2 in response) {
                        response2[counter] = response[key2];
                        counter++;
                    }

                    if(Object.keys(response2).length > 0){
                        localStorage.setItem("volumetricCart", JSON.stringify(response));
                    }else{
                        localStorage.removeItem("volumetricCart");
                    }

                }

            }

        }

    }

    function addToCartTax(self){

        return new Promise(function(resolve){

            let amount;
            amount = parseFloat($("#checkout-cart-panel #cart-summary #tax span:eq(1)").attr("data-amount"));

            if(amount !== 0){
                IdeaCart.addItem(self, {productId: data.taxId, quantity: amount});
            }

            setTimeout(function(){ resolve(true); }, 1500);

        });

    }

    function addToCartCargo(self){

        return new Promise(function(resolve){

            let amount;
            amount = parseFloat($("#cart-summary .cart-panel-amount-details .cart-panel-row:contains('Nakliye') span:eq(1)").attr("data-amount"));

            console.log(amount);
            
            if(amount !== 0){
                IdeaCart.addItem(self, {productId: data.cargoProductId, quantity: amount});
            }

            resolve(true);

        });

    }

    function editCheckout3(){

        let response;
        response = JSON.parse(localStorage.getItem("cartImage"));

        let target;
        target = $("#checkout-cart-panel #cart-summary .cart-panel-amount-details");

        target.html("");

        for (const key in response) {

            if(key !== "Toplam"){

                target.append(`<div class="cart-panel-row">
                    <span>${ key }</span>
                    <span>
                        ${ response[key] }
                    </span>
                </div>`);
            }else{

                target.append(`<div class="cart-panel-row cart-summary-total-price">
                    <span>${ key }</span>
                    <span>
                        <span id="cart-total-amount" data-general-amount="${ response[key].replace(".", "").replace(",", ".").replace(" EUR", "") }" data-final-amount="${ response[key].replace(" EUR", "") }">
                            ${ response[key] }
                        </span>
                    </span>
                </div>`);

            }

        }

        $("#checkout-cart-panel #cart-summary #cart-details #cart-items .cart-item").each(function(){

            let target;
            target = $(this).find(".cart-details a").attr("href");

            if(target.toLowerCase().indexOf("vergi") !== -1 || target.toLowerCase().indexOf("kargo") !== -1){
                $(this).addClass("d-none");
            }

        });

    }

    function clearProducts(){

        Swal.fire({
            title: "Sepetiniz düzenleniyor",
            html: $(`<div class="container d-flex justify-content-center overflow-hidden">
                <div>
                    <div class="row mb-3">
                        Lütfen bekleyiniz...
                    </div>
                    <div class="row d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-primary btn-loading bg-transparent disabled"></a>
                    </div>
                </div>
            </div>`),
            showConfirmButton: false,
            allowOutsideClick: false,
            willOpen: function(){

                return new Promise(function(resolve){

                    for (let index = 0; index < IdeaCart.items.length; index++) {

                        if(IdeaCart.items[index].product.id == data.cargoProductId){
                            IdeaCart.deleteItem($(this), IdeaCart.items[index].id);
                        }

                    }

                    setTimeout(function(){ resolve(true); }, 1500);

                }).then(function(response){

                    if(response){

                        for (let index = 0; index < IdeaCart.items.length; index++) {

                            if(IdeaCart.items[index].product.id == data.taxId){
                                IdeaCart.deleteItem($(this), IdeaCart.items[index].id);
                            }

                        }

                        setTimeout(function(){
                            localStorage.setItem("refresh", 1);
                            window.location.reload();
                        }, 1500);

                    }

                });

            }

        });

    }

    function cartController(){

        let response;
        response = JSON.parse(localStorage.getItem("volumetricCart"));

        let hasItem;

        for (const key in response) {

            hasItem = false;

            $("#cart-items").find(".cart-item").each(function(){

                console.log(response[key].dataId == $(this).find("[data-selector='delete-cart-item']").attr("data-id"));

                if(parseInt(response[key].dataId) == parseInt($(this).find("[data-selector='delete-cart-item']").attr("data-id"))){
                    hasItem = true;
                }

            });

            if(hasItem === false){
                delete response[key];

                let response2;
                response2 = new Object();

                let counter;
                counter = 0;

                for (const key in response) {
                    response2[counter] = response[key];
                    counter++;
                }

                console.log(response2);

                localStorage.setItem("volumetricCart", JSON.stringify(response2));
            }
        }

    }

    function clearLocalStorage(){

        let response;
        response = JSON.parse(localStorage.getItem("volumetricCart"));

        if(response !== null){

            let storageDataId;
            let itemDelete;

            for (const key in response) {
                
                storageDataId = response[key].dataId;
                itemDelete = true;

                $("#cart-container #cart-content #cart-items .cart-item [data-selector='delete-cart-item']").each(function(){

                    if($(window).width() > 992 && $(this).hasClass("cart-item-delete-mobile") === false){

                        if(parseInt($(this).attr("data-id")) === parseInt(storageDataId)){
                            itemDelete = false;
                        }

                    }else if($(window).width() <= 992 && $(this).hasClass("cart-item-delete") === false){
                        
                        if(parseInt($(this).attr("data-id")) === parseInt(storageDataId)){
                            itemDelete = false;
                        }

                    }
    
                });

                if(itemDelete !== false){
                    delete response[key];
                }

            }

            let response2;
            response2 = new Object();

            let counter;
            counter = 0;
            
            for (const key in response) {
                response2[counter] = response[key];
                counter++;
            }
            
            localStorage.setItem("volumetricCart", JSON.stringify(response2));

        }

    }

    $(document).ready(function(){

        let page;
        page = window.location.href;

        let targetHref;
        targetHref = $("#cart-panel a[href='/uye-girisi?next=order2'], #cart-panel a[href='/order/step2']").attr("href");

        $("#cart-panel a[href='/uye-girisi?next=order2'], #cart-panel a[href='/order/step2']").attr("href", "javascript:void(0);").attr("id", "complateShopping");
        $("#checkout-cart-panel #cart-summary [data-selector='submit-button']").removeAttr("data-selector").attr("id", "submit-button");

        $.get("https://www.modalife.furniture/dosya/countries.json", function(response){
            
            data.cargoProductId = response[0]["Cargo Product Id"];
            data.cargoId = response[0]["Cargo Id"];
            data.taxId = response[0]["Tax Id"];

            data.countries = new Array();

            $("header").append("<style>.overflow-hidden{ overflow: hidden !important; }</style>");

            for (let index = 1; index < response.length; index++) {
                data.countries.push(response[index]);
            }

            console.log(data);

            $("#checkout-cargo-details-content .contentbox-body .radio-custom").each(function(){

                if($(this).find("input").val() == data.cargoId){
                    $(this).find("label .cargo-right .label-price strong").text(number_format(0, 2, ",", ".") + " EUR");
                    $(this).find("input")[0].checked = true;
                }
    
            });

            if($("#address-list").length > 0 && page.indexOf("/step2") !== -1){
                editCheckout2();
            }else if(page.indexOf("/step2") !== -1){
                editAddressForm();
                appendZipCode();
            }

            if(page.indexOf("/sepet") !== -1){
                clearLocalStorage();
            }

            if(page.indexOf("/step3") !== -1){
                editCheckout3();
                localStorage.setItem("refresh2", 1);
            }

            if(page.indexOf("/step3") == -1){

                let response;
                response = localStorage.getItem("refresh");

                let response2;
                response2 = localStorage.getItem("refresh2");

                if(response !== null && response2 !== null){

                    for (let index = 0; index < IdeaCart.items.length; index++) {

                        if(IdeaCart.items[index].product.id == data.cargoProductId || IdeaCart.items[index].product.id == data.taxId){

                            localStorage.removeItem("refresh");
                            window.location.reload();

                        }

                    }

                }else if(response2 !== null){
                    clearProducts();
                }

                localStorage.removeItem("cartImage");

            }

            $(document).on("change", "#shippingCountry", function(){

                let dataCountryCode;
                dataCountryCode = $(this).find("option:selected").attr("data-country-code");

                listOfStates(dataCountryCode);
                taxAmount(dataCountryCode);
                cargoAmount(0, dataCountryCode);

            });

            $(document).on("change", "#country", function(){

                let dataCountryCode;
                dataCountryCode = $(this).find("option:selected").attr("data-country-code");

                listOfStates2(dataCountryCode);

            });

            $(document).on("change", "#shippingLocation2", function(){

                let dataCountryCode;
                dataCountryCode = $(this).parents(".row:eq(0)").find("#shippingCountry option:selected").attr("data-country-code");

                let value;
                value = parseFloat($(this).find("option:selected").val());

                $(this).parents(".row:eq(0)").find("#shippingLocation").val($(this).find("option:selected").text()).trigger("change");
                cargoAmount(value, dataCountryCode);

            });

            $(document).on("change", "#location2", function(){

                let value;
                value = parseFloat($(this).find("option:selected").val());

                $(this).parents(".row:eq(0)").find("#location").val($(this).find("option:selected").text()).trigger("change");

            });

            $(document).on("DOMNodeRemoved", ".loading-bar, #editAddressForm, #addAddressForm", function(){
                
                let dataCountryCode;
                dataCountryCode = $("#shippingCountry option:selected").attr("data-country-code");

                let cargo;
                cargo = $("#shippingLocation2 option:selected").val();

                $("#cart-panel a[href='/uye-girisi?next=order2'], #cart-panel a[href='/order/step2']").attr("href", "javascript:void(0);").attr("id", "complateShopping");

                if(typeof dataCountryCode !== "undefined"){
                    taxAmount(dataCountryCode);
                }else{
                    taxAmount();
                }

                if(typeof cargoAmount !== "undefined"){
                    cargoAmount(cargo, dataCountryCode);
                }

                $("#cart-items .cart-item [data-selector='delete-cart-item']").each(function(){

                    if($(window).width() > 992 && $(this).hasClass("cart-item-delete-mobile") === false){
                        volumetricCart2($(this).attr("data-id"), parseFloat($(this).parents(".cart-item-detail").find("[data-selector='qty']").val()));
                    }else if($(window).width() < 992 && $(this).hasClass("cart-item-delete") === false){
                        volumetricCart2($(this).attr("data-id"), parseFloat($(this).parents(".cart-item").find(".cart-item-detail").find("[data-selector='qty']").val()));
                    }

                });

                if($("#address-list").length > 0){
                    editCheckout2();
                }

                if(page.indexOf("/step3") !== -1){
                    editCheckout3();
                }

                $("#step2Form #address-list [data-type='shipping-address-wrapper'] .address-block-header").removeClass("validate-error");

            });

            $(document).on("click", ".product-right [data-selector='add-to-cart'], .product-right-mobile [data-selector='add-to-cart']", function(){

                let pId;
                pId = $(this).attr("data-product-id");

                let qty;
                qty = $(this).parents(".product-cart-buttons:eq(0)").find("[data-selector='qty']").val();

                let interval;
                interval = setInterval(function(){

                    if($("#cart-popup-container").length > 0){
                        
                        clearInterval(interval);

                        $("#cart-popup-container #cart-items .cart-item [data-selector='delete-cart-item']").each(function(){

                            if($(window).width() > 992 && $(this).hasClass("cart-item-delete-mobile") === false && $(this).attr("data-product-id") == pId){
                                volumetricCart($(this).attr("data-id"), $(this).attr("data-product-id"), parseFloat(qty));
                            }else if($(window).width() <= 992 && $(this).hasClass("cart-item-delete") === false && $(this).attr("data-product-id") == pId){
                                volumetricCart($(this).attr("data-id"), $(this).attr("data-product-id"), parseFloat(qty));
                            }

                        });

                    }

                }, 500);

            });

            $(document).on("click", "#cart-popup-container [data-selector='delete-cart-item'], #cart-items [data-selector='delete-cart-item']", function(){

                if($(window).width() > 992 && $(this).hasClass("cart-item-delete-mobile") === false){
                    volumetricCart3($(this).attr("data-id"));
                }else if($(window).width() <= 992 && $(this).hasClass("cart-item-delete") === false){
                    volumetricCart3($(this).attr("data-id"));
                }

            });

            $(document).on("click", ".modal-dialog [data-selector='cart-item-delete']", function(){
                volumetricCart3($(this).attr("data-id"));
            });

            $(document).on("click", "#cart-panel #complateShopping", function(){

                if(complateShoppingTrigger === false){

                    let totalWeight;
                    totalWeight = 0;

                    let response;
                    response = JSON.parse(localStorage.getItem("volumetricCart"));

                    if(response !== null){

                        cartController();

                        for (const key in response) {
                            totalWeight += response[key].totalWeight;
                        }
                        
                        localStorage.setItem("volumetricTotalWeight", parseFloat(totalWeight.toFixed(2)));
                    }

                    complateShoppingTrigger = true;
                    $(this).attr("href", targetHref).trigger("click");

                }

            });

            $(document).on("click", "#address-list [data-type='shipping-address-wrapper'] [data-selector='edit-address-form']", function(){

                let interval;
                interval = setInterval(function(){

                    if($("#editAddressForm").length > 0){
                        
                        clearInterval(interval);

                        editAddressForm();
                        appendZipCode();

                        $("#editAddressForm [data-selector='submit-edit-address-form']").removeAttr("data-selector").attr("id", "submit-edit-address-form");

                    }

                });

            });

            $(document).on("click", "#editAddressForm #submit-edit-address-form", function(){

                if($(this).parents("#editAddressForm").valid() && editAddressFormTrigger == false){

                    let zipCode;
                    zipCode = $(this).parents("#editAddressForm").find("#zipCode").val();

                    let targetValue;
                    targetValue = $(this).parents("#editAddressForm").find("#address").val();

                    if(targetValue.indexOf(" | Posta Kodu = ") !== -1){
                        targetValue = targetValue.split(" | Posta Kodu = ")[0];
                    }

                    $(this).parents("#editAddressForm").find("#address").val(targetValue + " | Posta Kodu = " + zipCode).trigger("change");

                    editAddressFormTrigger = true;
                    $(this).removeAttr("id").attr("data-selector", "submit-edit-address-form");
                    $(this).trigger("click");

                }

                setTimeout(function(){ editAddressFormTrigger = false }, 500);

            });

            $(document).on("click", "#address-list [data-type='shipping-address-wrapper'] [data-selector='add-address-form']", function(){

                let interval;
                interval = setInterval(function(){

                    if($("#addAddressForm").length > 0){
                        
                        clearInterval(interval);

                        editAddressForm();
                        appendZipCode();

                        $("#addAddressForm [data-selector='submit-address-form']").removeAttr("data-selector").attr("id", "submit-address-form");

                    }

                });

            });

            $(document).on("click", "#addAddressForm #submit-address-form", function(){

                let self;
                self = $(this);

                if($(this).parents("#addAddressForm").valid() && editAddressFormTrigger == false){

                    let zipCode;
                    zipCode = $(this).parents("#addAddressForm").find("#zipCode").val();

                    let targetValue;
                    targetValue = $(this).parents("#addAddressForm").find("#address").val();

                    if(targetValue.indexOf(" | Posta Kodu = ") !== -1){
                        targetValue = targetValue.split(" | Posta Kodu = ")[0];
                    }

                    $(this).parents("#addAddressForm").find("#address").val(targetValue + " | Posta Kodu = " + zipCode).trigger("change");

                    addAddressFormTrigger = true;
                    $(this).removeAttr("id").attr("data-selector", "submit-address-form");
                    $(this).trigger("click");

                }

                setTimeout(function(){ 
                    editAddressFormTrigger = false
                    self.attr("id", "submit-address-form").removeAttr("data-selector");
                }, 500);

            });

            $(document).on("click", "#checkout-cart-panel #cart-summary #submit-button", function(){

                let self;
                self = $(this);

                if($("#address-list").length > 0){

                    let address;
                    address = $("#step2Form #address-list [data-type='shipping-address-wrapper'] .address-block-header [data-type='shipping'] address p").text();

                    if(address.indexOf(" | Posta Kodu = ") === -1){
                        $("#step2Form #address-list [data-type='shipping-address-wrapper'] .address-block-header").addClass("validate-error");
                        return false;
                    }

                }else{

                    if(!$("#step2Form").valid()){
                        
                        if($("#step2Form #zipCode").val() === ""){
                            $("#step2Form #zipCode").addClass("validate-error");
                        }

                        if($("#step2Form #shippingLocation2 option:selected").val() === ""){
                            $("#step2Form #shippingLocation2").addClass("validate-error");
                        }

                        return false;
                    }

                    if($("#step2Form #shippingLocation2 option:selected").val() === ""){
                        $("#step2Form #shippingLocation2").addClass("validate-error");
                        return false;
                    }

                    if($("#step2Form #zipCode").val() === ""){
                        $("#step2Form #zipCode").addClass("validate-error");
                        return false;
                    }

                    let zipCode;
                    zipCode = $("#step2Form #zipCode").val();

                    let targetValue;
                    targetValue = $("#step2Form #address").val();

                    if(targetValue.indexOf(" | Posta Kodu = ") !== -1){
                        targetValue = targetValue.split(" | Posta Kodu = ")[0];
                    }

                    $("#step2Form #address").val(targetValue + " | Posta Kodu = " + zipCode).trigger("change");

                }

                $(this).addClass("btn-loading disabled");

                addToCartTax($(this)).then(function(response){
                        
                    if(response !== false){

                        addToCartCargo(self).then(function(response2){

                            if(response2 !== false){

                                let cartImage;
                                cartImage = new Object();

                                $("#checkout-cart-panel #cart-summary .cart-panel-amount-details .cart-panel-row").each(function(){
                                    cartImage[$(this).find("span:eq(0)").text().trim()] = $(this).find("span:eq(1)").text().trim();
                                });

                                localStorage.setItem("cartImage", JSON.stringify(cartImage));
                                $("#step2Form").submit();

                            }

                        });

                    }

                });

            });

            $(document).on("click", "[data-selector='edit-address-button'], [data-selector='add-address-button']", function(){

                let interval;
                interval = setInterval(function(){

                    if($(".fancybox-container").length > 0){

                        clearInterval(interval);

                        editAddressForm2();
                        appendZipCode2();

                        $("[data-selector='edit-address-form-button']").removeAttr("data-selector").attr("id", "edit-address-form-button");
                        $("[data-selector='add-address-form-button']").removeAttr("data-selector").attr("id", "add-address-form-button");

                    }

                });

            });

            $(document).on("click", "[data-selector='edit-address-form'] #edit-address-form-button", function(){

                let self;
                self = $(this);

                if($(this).parents("[data-selector='edit-address-form']").valid() && editAddressFormTrigger == false){

                    let zipCode;
                    zipCode = $(this).parents("[data-selector='edit-address-form']").find("#zipCode");

                    if(zipCode.val() == ""){
                        $(this).parents("[data-selector='edit-address-form']").find("#zipCode").addClass("validate-error");
                        return false;
                    }

                    let targetValue;
                    targetValue = $(this).parents("[data-selector='edit-address-form']").find("textarea[name='address']").val();

                    if(targetValue.indexOf(" | Posta Kodu = ") !== -1){
                        targetValue = targetValue.split(" | Posta Kodu = ")[0];
                    }

                    $(this).parents("[data-selector='edit-address-form']").find("textarea[name='address']").val(targetValue + " | Posta Kodu = " + zipCode.val()).trigger("change");

                    editAddressFormTrigger = true;
                    $(this).removeAttr("id").attr("data-selector", "edit-address-form-button");
                    $(this).trigger("click");

                }

                setTimeout(function(){ editAddressFormTrigger = false; self.removeAttr("data-selector"); }, 500);

            });

            $(document).on("click", "[data-selector='add-address-form'] #add-address-form-button", function(){

                if($(this).parents("[data-selector='add-address-form']").valid() && editAddressFormTrigger == false){

                    let zipCode;
                    zipCode = $(this).parents("[data-selector='add-address-form']").find("#zipCode");

                    if(zipCode.val() == ""){
                        $(this).parents("[data-selector='add-address-form']").find("#zipCode").addClass("validate-error");
                        return false;
                    }

                    let targetValue;
                    targetValue = $(this).parents("[data-selector='add-address-form']").find("textarea[name='address']").val();

                    if(targetValue.indexOf(" | Posta Kodu = ") !== -1){
                        targetValue = targetValue.split(" | Posta Kodu = ")[0];
                    }

                    $(this).parents("[data-selector='add-address-form']").find("textarea[name='address']").val(targetValue + " | Posta Kodu = " + zipCode.val()).trigger("change");

                    editAddressFormTrigger = true;
                    $(this).removeAttr("id").attr("data-selector", "add-address-form-button");
                    $(this).trigger("click");

                }

                setTimeout(function(){ editAddressFormTrigger = false; self.removeAttr("data-selector"); }, 500);

            });
            
        });

    });

})(jQuery);