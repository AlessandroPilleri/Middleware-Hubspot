<?php

    class Company
    {
        var $name;
        var $codice_fiscale_p_iva;
        var $codice_cliente;
        var $classificazione_clienti;
        var $customer_group;
        var $e_fornitore;
        var $fax;
        var $partita_iva_cee;
        var $sales_group_gav;
        var $country;
        var $state;
        var $city;
        var $address;
        var $address2;
        var $zip;
        var $phone;
        var $companyId;
        var $isDeleted;
        var $domain;
        var $settore_industriale;
        var $ordinato_anno_corrente;
        var $ordinato_anno_precedente;
        var $data_ultimo_ordine;
        var $ordinato_anno_precedente_ytd;
        var $fonte = 'Cliente SAP';

        function __construct($company)
        {
            if($company['RagioneSociale'] != null)
                $this->name = $company['RagioneSociale'] . '' .  ($company['RagioneSociale2'] != 'NULL' ? ' ' . $company['RagioneSociale2']  : '') ;

            $this->codice_fiscale_p_iva = $company['piva'];
            $this->codice_cliente = $company['cliente'];
            $this->classificazione_clienti = $company['classificazione'];

            if($company['fornitore'] != null)
                $this->e_fornitore = true;

            if($company['gruppoclienti'] == 'Trasf. a  Dealer')
                $this->customer_group = 'Trasf. a Dealer';
            else
                $this->customer_group = $company['gruppoclienti'];

            if($company['gav'] != null || $company['addVendite'] != null)
                $this->sales_group_gav = $company['gav'] . ' ' . $company['addVendite'];

            $this->fax = $company['phonetelefax'];
            $this->partita_iva_cee = $company['pivacee'];
            $this->country = $company['pse'];
            $this->state = $company['rg'];
            $this->city = $company['localita'];
            $this->address = $company['via'];
            //$this->address2 = $company[''];
            $this->zip = $company['cap'];
            $this->phone = $company['phone'];
            $this->companyId = $company['companyId'];
            $this->isDeleted = $company['isDeleted'];
            $this->domain = substr($company['dominioemail'], 1);
            $this->settore_industriale = $company['chiavesettoreindustriale'];
            $this->ordinato_anno_corrente = $company['SUM(GCNetValue)'];
            $this->data_ultimo_ordine = $company['MAX(SalesDocumentDate)'];
            $this->ordinato_anno_precedente = $company['previousYearNetValue'];
            $this->ordinato_anno_precedente_ytd = $company['ordinatoYTD'];
        }
    }
 ?>
