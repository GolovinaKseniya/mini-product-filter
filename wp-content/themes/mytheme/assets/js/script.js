var categories = start_data.categories;

class Burger
{
    data = {
        bun: {
            limit   : -1,
            required: true,
            items   : []
        },

        cheese: {
            limit   : -1,
            required: true,
            items   : []
        },

        cutlet: {
            limit   : -1,
            required: true,
            items   : []
        }
    };

    setItem(key, id) {
        if ((this.data[key].limit > this.data[key].items.length) && (!this.data[key].items.includes(id))) {
            this.data[key].items.push(id);
        } else if (this.data[key].limit === -1) {
            this.data[key].items.push(id);
        }
    }

    removeItem(key, id) {
        this.data[key].items = this.data[key].items.filter((itemId) => itemId !== id);
    }
}

var burgerData = new Burger();

for (var category in categories) {
    var title = categories[category].title;

    if (categories[category].limit !== "") {
        burgerData.data[title].limit = categories[category].limit;
    }
    burgerData.data[title].required = categories[category].required;
}

function appendItemToCart(item, id, key) {
    var cartClass = "result-" + key;

    $(".modal-cart__content").append(
        "<div " +
        "data-id = '" + id + "' " +
        "class = '" + cartClass + " d-flex items-center " + id +
        "'><p class='m-0'>"
        + $(item).children(".product__title").text() + " - "
        + $(item).children(".product__price").text() +
        "</p>" +
        "<div class='remove-item'>&times;</div>" +
        "</div>");
}

function itemClick(item, key) {
    var itemSelected = "item-selected";
    var itemDisabled = "item-disabled";
    var id = $(item).attr("id");
    var max = burgerData.data[key].limit;

    $(".filter").children("." + key).each(function () {
        $(this).removeClass(itemDisabled);
    });

    if (!$(item).hasClass(itemSelected)) {
        burgerData.setItem(key, id);

        var alert = $(".alert");
        $(".alert-close").click(function () {
            $(this).parent().hide();
        })

        alert.show();
        alert.find("p").text("You add + 1 " + key);
        setTimeout(function () {
            alert.hide();
        }, 4000);

        $(item).addClass(itemSelected);
    }
    $(item).data('count', $(item).data('count') + 1);

    appendItemToCart(item, id, key);

    if ($(item).data('count') > 1) {
        $(item).data('count', 0);

        burgerData.removeItem(key, id);

        $(item).removeClass(itemSelected);
        $("." + id).remove();
    }

    if (burgerData.data[key].items.length === +max) {
        $(".filter").children("." + key).each(function () {
            if (!$(this).hasClass(itemSelected)) {
                $(this).addClass(itemDisabled);
            }
        });
    }
}

function removeItemFromCart(item, key, id) {
    var cartClass = "result-" + key;
    var itemDisabled = "item-disabled";
    var itemSelected = "item-selected";


    if ($(item).parent().hasClass(cartClass)) {
        burgerData.removeItem(key, id);

        $(".filter").children("." + key).each(function () {
            $(this).removeClass(itemDisabled);
        });
    }

    $("#" + id).removeClass(itemSelected);
    $("#" + id).data('count', 0);
}


$(document).ready(function () {
    $(document).on('click', '.remove-item', function () {
        var product_id = "" + $(this).parent().data('id');

        removeItemFromCart(this, 'bun', product_id);
        removeItemFromCart(this, 'cheese', product_id);
        removeItemFromCart(this, 'cutlet', product_id);

        $(this).parent().remove();
    });

    $(".cheese").click(function () {
        itemClick(this, 'cheese');
    });

    $(".bun").click(function () {
        itemClick(this, 'bun');
    });

    $(".cutlet").click(function () {
        itemClick(this, 'cutlet');
    });

    $(".cart").click(function () {
        $(".modal-cart").slideToggle();
    });

    $("#max").val(start_data.max_price);

    $("#slider-range").slider({
        range : true,
        min   : 0,
        max   : start_data.max_price,
        values: [0, start_data.max_price],
        slide : function (event, ui) {
            $("#min").val(ui.values[0])
            $("#max").val(ui.values[1])
        }
    });

    $("#filter").click(function () {
        var form = $("#form").serialize();
        $(".loader").css('visibility', 'visible');

        jQuery(function ($) {
            $.ajax({
                url    : start_data.url,
                type   : 'POST',
                data   : form,
                success: function (data) {
                    $(".loader").css('visibility', 'hidden');

                    var idArray = data.result;

                    $(".product").each((i, el) => {
                        el.classList.add("hide");
                        if (idArray.includes(+el.dataset.check)) {
                            el.classList.add("show");
                            el.classList.remove("hide");
                        }

                    })
                },
                error  : function (xhr, status, error) {
                    var errorMessage = xhr.status + ': ' + xhr.statusText;
                    alert('Error - ' + errorMessage);
                }
            });

        });
    });


    var checkout_url = start_data.checkout_url;

    $(".get-burger").click(function () {
        var result = {
            action: 'function',
            param: []
        };

        for (var item in burgerData.data) {
            var array = burgerData.data[item].items;
            result['param'] = result['param'].concat(array);
        }

        if (result.length === 0) {
            alert("Your cart is empty");
        } else {
            jQuery(function ($) {
                $.ajax({
                    url       : start_data.url,
                    type      : 'POST',
                    data      : result,
                    success   : function (data) {
                        switch (data.response_code) {
                            case 200:
                                window.location = checkout_url;
                                break;
                            case 400:
                                alert("These categories are required to order: " + data.required_categories);
                                break;
                            default:
                                alert("Something go wrong");
                        }
                    },
                    error     : function (xhr, status, error) {
                        var errorMessage = xhr.status + ': ' + xhr.statusText;
                    }
                });

            });
        }
    })
});

