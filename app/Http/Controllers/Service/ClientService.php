<?php
namespace App\Http\Service;
use App\Http\Service\DynamicsCrmHeader;
use App\Http\Service\DynamicsCrmSoapClient;
use App\Http\Service\DynamicsCrmSoapHeaderInfo;

class ClientService
{

    protected $url;

    protected $username;

    protected $password;


    protected $authHeader;
    /**
     * Client constructor.
     */
    public function __construct($username, $password, $url)
    {
        $this->username =$username;
        $this->password =$password;
        $this->url =$url;
        $dynamicsCrmHeader = new DynamicsCrmHeader ();
        $this->authHeader = $dynamicsCrmHeader->GetHeaderOnline ( $username, $password, $url );

    }


    public function retriveCrmData($entity_name, $condition = [])
    {

        $xml ="<s:Body>";
        $xml .="<Execute xmlns=\"http://schemas.microsoft.com/xrm/2011/Contracts/Services\" xmlns:i=\"http://www.w3.org/2001/XMLSchema-instance\">";
        $xml .="<request i:type=\"a:RetrieveMultipleRequest\" xmlns:a=\"http://schemas.microsoft.com/xrm/2011/Contracts\">";
        $xml .="<a:Parameters xmlns:b=\"http://schemas.datacontract.org/2004/07/System.Collections.Generic\">";
        $xml .="<a:KeyValuePairOfstringanyType>";
        $xml .="<b:key>Query</b:key>";
        $xml .="<b:value i:type=\"a:QueryExpression\">";
        $xml .="<a:ColumnSet>";
        $xml .="<a:AllColumns>true</a:AllColumns>";

        $xml .="<a:Columns xmlns:c=\"http://schemas.microsoft.com/2003/10/Serialization/Arrays\">";
        $xml .="</a:Columns>";
        $xml .="</a:ColumnSet>";
        $xml .= "<a:Criteria>";
        $xml .= "<a:Conditions>";


        if(!empty($condition))
        {
            foreach ($condition as $key=>$value)
            {
                $xml .= "<a:ConditionExpression >";
                $xml .= "<a:AttributeName>". $key ."</a:AttributeName>";
                $xml .= "<a:Operator>Equal</a:Operator>";
                $xml .= "<a:Values xmlns:c=\"http://schemas.microsoft.com/2003/10/Serialization/Arrays\">";
                $xml .= "<c:anyType i:type=\"d:string\" xmlns:d=\"http://www.w3.org/2001/XMLSchema\">". $value ."</c:anyType>";
                $xml .= "</a:Values>";
                $xml .= "</a:ConditionExpression>";
            }
        }



        $xml .= "</a:Conditions>";
        $xml .= "<a:FilterOperator>And</a:FilterOperator>";
        $xml .= "<a:Filters />";
        $xml .= "</a:Criteria>";

        $xml .="<a:Distinct>false</a:Distinct>";
        $xml .="<a:EntityName>". $entity_name ."</a:EntityName>";
        $xml .="<a:LinkEntities />";
        $xml .="<a:Orders />";
        $xml .="<a:PageInfo>";
        $xml .="<a:Count>0</a:Count>";
        $xml .="<a:PageNumber>0</a:PageNumber>";
        $xml .="<a:PagingCookie i:nil=\"true\" />";
        $xml .="<a:ReturnTotalRecordCount>false</a:ReturnTotalRecordCount>";
        $xml .="</a:PageInfo>";
        $xml .="<a:NoLock>false</a:NoLock>";
        $xml .="</b:value>";
        $xml .="</a:KeyValuePairOfstringanyType>";
        $xml .="</a:Parameters>";
        $xml .="<a:RequestId i:nil=\"true\" />";
        $xml .="<a:RequestName>RetrieveMultiple</a:RequestName>";
        $xml .="</request>";
        $xml .="</Execute>";
        $xml .="</s:Body>";


        $client = new DynamicsCrmSoapClient ();

        $response = $client->ExecuteSOAPRequest ( $this->authHeader, $xml, $this->url    );
        $responsedom = new \DomDocument ();

        $responsedom->loadXML ( $response );
        $values = $responsedom->getElementsbyTagName ("Entity" );

        $listArray = array();
        foreach ( $values as $value ) {
            $objArray=array();
            foreach ( $value->firstChild->getElementsbyTagName("KeyValuePairOfstringanyType") as $KeyValuePairOfstringanyType )
            {

                $objArray[$KeyValuePairOfstringanyType->firstChild->textContent] = $KeyValuePairOfstringanyType->lastChild->firstChild->textContent;

            }
            $listArray[]=$objArray;
        }
        return $listArray ;

    }
}