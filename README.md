# 🇧🇷 Webkul e PagBank - Split
Módulo para customizar os valores enviados para split do módulo webkul/split-magento com dados obtidos do Webkul Marketplace.

> __Warning__ Antes de subir em produção é necessário homologação. Entre em contato com pagbank@o2ti.com para inicio do processo.

## Características

Esse módulo tem estrita dependência de:
- [pagseguro/payment-magento](https://github.com/pagseguro/payment-magento)
- [pagseguro/split-magento](https://github.com/pagseguro/split-magento)
- [WebKul Marketplace](https://store.webkul.com/magento2-multi-vendor-marketplace.html)

### Definição da conta PagBank no frontend da loja

Uma nova página é criada no perfil dos vendedores, onde o vendedor pode vincular sua conta PagBank.

### Cálculo de comissão

Conforme você configura o Split no módulo Webkul, o valor é repassado para a conta PagBank do seu vendedor.

### Repasse de Frete

Caso utilize o módulo de frete [Frenet da Webkul](https://store.webkul.com/Magento2-Marketplace-Frenet-Shipping.html), o valor calculado para cada vendedor será repassado.


# 🇬🇧 Webkul and PagBank - Split
Module to customize the values sent for split using the pagbank/split-magento module with data obtained from the Webkul Marketplace.

> __Warning__  Before going into production, homologation is required. Please contact pagbank@o2ti.com to start the process.

## Features

This module has strict dependencies on:
- [pagseguro/payment-magento](https://github.com/pagseguro/payment-magento)
- [pagseguro/split-magento](https://github.com/pagseguro/split-magento)
- [WebKul Marketplace](https://store.webkul.com/magento2-multi-vendor-marketplace.html)

### Definition of PagBank account on the store frontend

A new page is created in the sellers' profile, where the seller can link their PagBank account.

### Commission Calculation

As you configure the Split in the Webkul module, the value is passed on to your seller's PagBank account.

### Freight Passing

If you use the [Frenet shipping module from Webkul](https://store.webkul.com/Magento2-Marketplace-Frenet-Shipping.html), the calculated value for each seller will be passed on.
