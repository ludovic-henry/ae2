idNumberPre = "nbProd";
idPricePre = "priceProd";
idTabsList = "productsTabs";
idPlatProd = "platProd";

var total;
var currentBalance;
var newProducts = new Array();
var tabsId = new Array();

function getTabsId()
{
	if (tabsId.length==0)
	{
		ulTabs = document.getElementById(idTabsList);

		for (i=0;i<ulTabs.childNodes.length;i++)
		{
			tabsId.push(ulTabs.childNodes.item(i).firstChild.id);
		}
	}

	return tabsId;
}

function getTotal() {
	//if (typeof(total)=='undefined')
	{
		total = Math.round(parseFloat(document.getElementById('priceTotal').firstChild.nodeValue.replace(',', '.'))*100);
	}
	return total;
}

function addTotal(price) {
	//if (typeof(total)=='undefined')
	{
		total = Math.round(parseFloat(document.getElementById('priceTotal').firstChild.nodeValue.replace(',', '.'))*100);
	}
	
	total+=price;

	if (total<0)
	{
		total=0;
	}
	document.getElementById('priceTotal').firstChild.nodeValue = total/100;

	return total;
}

function removeTotal(price) {
	return addTotal(-price);
}

function getCurrentBalance() {
	if (typeof(currentBalance)=='undefined')
	{
		currentBalance = Math.round(parseFloat(document.getElementById('soldeCourant').firstChild.nodeValue.replace(',', '.'))*100);
	}
	return currentBalance;
}

function changeActiveTab(eltId) {
	var arrLinkId = getTabsId();
	var strContent = new String();
	for (i=0; i<arrLinkId.length; i++) {
		strContent = arrLinkId[i]+"Contents";
		if ( arrLinkId[i] == eltId ) {
			document.getElementById(arrLinkId[i]).className = "typeProdTab current";
			document.getElementById(strContent).className = 'products';
		} else {
			document.getElementById(arrLinkId[i]).className = "typeProdTab";
			document.getElementById(strContent).className = 'products hide';
		}
	}

	return false;
}

function increase(code_barre, price, barmanPrice, plateau, barman)
{
	tdNumber = document.getElementById(idNumberPre+code_barre);
	tdPrice = document.getElementById(idPricePre+code_barre);
	nombre = (parseInt(tdNumber.firstChild.nodeValue) + 1);
	nombrePlateau = nombre - Math.floor(nombre/6);

	prixActuel = parseFloat(tdPrice.firstChild.nodeValue.replace(',', '.'))*100;
	prixBarman = nombre * barmanPrice;
	prix = (plateau ? nombrePlateau:nombre)*price;
	diff = ((barman && (prixBarman < prix))?prixBarman:prix) - prixActuel;

	if (isProductCanBeAdded(diff))
	{
		tdPrice = document.getElementById(idPricePre+code_barre);
		tdNumber.firstChild.nodeValue=parseInt(tdNumber.firstChild.nodeValue)+1;

		  newValue = Math.round(parseFloat(tdPrice.firstChild.nodeValue.replace(',', '.'))*100+diff);
		  tdPrice.firstChild.nodeValue=newValue/100 + " \u20AC";

		  increaseTotal(diff);

    if (plateau) {
      if (parseInt(tdNumber.firstChild.nodeValue) >= 6)
        document.getElementById(idPlatProd+code_barre).innerHTML = 'P';
    }

		addToNewProductsFields(code_barre);
	}
	else
	{
		alert('Solde insuffisant');
	}
	return false;
}

function decrease(code_barre, price, barmanPrice, plateau, barman)
{
	tdNumber = document.getElementById(idNumberPre+code_barre);
	tdPrice = document.getElementById(idPricePre+code_barre);
	nombre = (parseInt(tdNumber.firstChild.nodeValue)-1);
	if(nombre<0)
		nombre = 0;
	nombrePlateau = nombre - Math.floor(nombre/6);

	prixActuel = parseFloat(tdPrice.firstChild.nodeValue.replace(',', '.'))*100;
	prixBarman = nombre * barmanPrice;
	prix = (plateau ? nombrePlateau:nombre)*price;
	diff = prixActuel - ((barman && (prixBarman < prix))?prixBarman:prix);


		tdNumber.firstChild.nodeValue = nombre;

		  newPrice = Math.round(prixActuel-diff);
		  tdPrice.firstChild.nodeValue=newPrice/100 + " \u20AC";

		  decreaseTotal(diff);

    if (plateau) {
      if (parseInt(tdNumber.firstChild.nodeValue) < 6)
        document.getElementById(idPlatProd+code_barre).innerHTML = '';
    }
	addToNewProductsFields("-"+code_barre);

	return false;
}

function increaseTotal(price)
{
	addTotal(price);

	tdTotalPrice = document.getElementById("priceTotal");

	tdTotalPrice.firstChild.nodeValue = getTotal()/100 + " \u20AC";

	return false;
}

function decreaseTotal(price)
{
	removeTotal(price);

	tdTotalPrice = document.getElementById("priceTotal");

	tdTotalPrice.firstChild.nodeValue = getTotal()/100 + " \u20AC";

	return false;
}

function addToCart(code_barre, nom, prix, prixBarman, plateau, barman)
{
	if (isProductCanBeAdded(barman?prixBarman:prix))
	{
		if (!document.getElementById('prod'+code_barre))
		{
			addProductRow(code_barre, nom, prix, prixBarman, plateau, barman);
			addToNewProductsFields(code_barre);
		}
		else
		{
			increase (code_barre, prix, prixBarman, plateau, barman);
		}
	}
	else
	{
		alert('Solde insuffisant');
	}

	return false;
}

function addProductRow(code_barre, nom, prix, prixBarman, plateau, barman)
{
	var table = document.getElementById("panier");

	var newRow;

	if (document.getElementById("total"))
	{
		newRow = panier.insertRow(document.getElementById("total").rowIndex);
	}
	else
	{
		newRow = panier.insertRow(-1);
	}

	newRow.id = "prod"+code_barre;

	var newCell = newRow.insertCell(-1);
	newCell.innerHTML = "<a onclick=\"return decrease('"+code_barre+"', "+prix+", "+prixBarman+", "+plateau+", "+barman+");\" href=\"#\">-</a>";

	newCell = newRow.insertCell(-1);
	newCell.id = idNumberPre+code_barre;
	newCell.innerHTML = "1";

	newCell = newRow.insertCell(-1);
	newCell.innerHTML = "<a onclick=\"return increase('"+code_barre+"', "+prix+", "+prixBarman+", "+plateau+", "+barman+");\" href=\"#\">+</a>";

	newCell = newRow.insertCell(-1);
	newCell.innerHTML = nom;

  newCell = newRow.insertCell(-1);
  newCell.id = idPlatProd+code_barre;
  newCell.innerHTML = '';

	newCell = newRow.insertCell(-1);
	newCell.id = idPricePre+code_barre;
	newCell.innerHTML = (barman?prixBarman:prix)/100+" \u20AC";

	increaseTotal(barman?prixBarman:prix);
}

function checkBarCodeInput()
{
	var inputCodeBarre = document.getElementById("code_barre");

	if (inputCodeBarre.value)
	{
		var arrayNouveauxProduitsHidden = document.getElementsByName('nouveaux_produits');

		for (var i=0; i<arrayNouveauxProduitsHidden.length; i++)
		{
			arrayNouveauxProduitsHidden[i].value += inputCodeBarre.value;
		}
	}

	return true;
}

function addToNewProductsFields(barCode)
{
	var arrayNouveauxProduitsHidden = document.getElementsByName('nouveaux_produits');

	for (var i=0; i<arrayNouveauxProduitsHidden.length; i++)
	{
		arrayNouveauxProduitsHidden[i].value += barCode+";";
	}

	return true;
}

function isProductCanBeAdded(price)
{
	return ((getTotal()+price)<=getCurrentBalance());
}
