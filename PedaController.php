<?php
//require_once 'Base480Controller.php';
//class Inventario_FactController extends Base480Controller
require_once 'BaseNiv3480Controller.php';
class Gerencia_PedaController extends Baseniv3480Controller
{
    public function init(){
        parent::init();
        
        $this->titulo='APROBACION DE PEDIDOS';
        $this->mainform=new Gerencia_Form_Pedaform();
        $this->mainmodel=new Inventario_Model_Factmodel();
        $this->list_fields=array('Cliente','TipoMovimiento','Referencia','Fecha','Vencimiento','FechaVeSC','TotalDocumento','Monto','MontoAprobado');
        //OJO TODOS los campos de tablas no principales en un join deben ser precedidas por dummy u otra letra para
        //que no sean repetidas en el innerlist del sql del pagineo
        
        $this->ListConvert=array('Fecha'=>'SQLtoLatin');
        $this->list_titles=array('Cod','TipoMovimiento','Referencia','Fec.Emi','Fec.Pago','Fec.Venc','Monto','Saldo','Monto Aprobado');
        
        $this->sort_order=array('Fecha desc','TipoMovimiento asc');
        $this->newmainsort='Fecha desc';
        $this->sqlstatement="SELECT * FROM en01depr  where (EstadoDocumento=0) and (abs(TotalDocumento-TotalCreditos)>0.0001)";
        $this->recordsperpage=20;
        $this->maxshowpages=11;
        
        
        $this->scriptTagsAdd =  '<link href="/css/anchorpad.css" rel="stylesheet"/>'.
                                 '<script src="/js/nodyn/gerencia_peda.js?ver=1.a016"></script>'
                        ;
        
        $this->SubformaSub = array('recargos','comprobantes');
        
        $this->UseAltText=1;//Cuando Hay tablas muy grandes y queremos optimizar los select
        
        $this->UseSelect2=1;
        $this->Select2Elements=array('CodVendedor');
        $this->serializa=0;
        $this->encripta=0;
        
        $this->noMostrar=1;
        
        $modelVargen=new Default_Model_Defimodel;
        $vargen = $modelVargen->getSelectWhereOrderSQL('ActivarPrepago','1=1', '');
        $vargen=$vargen[0];
        $this->ActivarPrepago=$vargen['ActivarPrepago'];
        
    }
    
    public function asignaOpcionesForm($param=array())         
        {
            /*
            //bitwise comparison
            $values = array(0, 1, 2, 4,256, 260);
            $test = 4;
            $format = '(%1$2d = %1$04b) = (%2$2d = %2$04b)'
                    . ' %3$s (%4$2d = %4$04b)' . "<br>";
            echo "\n Bitwise AND <br>";
            foreach ($values as $value) {
                $result = $value & $test;
                printf($format, $result, $value, '&', $test);
            }
             * 
             */
             
            
            $dbparam=Zend_Registry::get('dbparam');
            $dbsec = Zend_Db::factory($dbparam['dll'], array(
                'host'     => $dbparam['host'],
                'username' => $dbparam['user'],
                'password' => $dbparam['pass'],
                'dbname'   => $dbparam['dbnamesec'], 
                'driver_options' => $dbparam['driver_options']
                 ));
            
            
            $regScaw = $dbsec->fetchall("SELECT Identificacion as Nombre,IdentificacionDigital,Rango FROM EN01USUA where IdentificacionDigital<>0"); 
           
            foreach ($regScaw as $reg) {
               $rangosxfirma[$reg['IdentificacionDigital']]=(int) $reg['Rango'];
               $nombresxfirma[$reg['IdentificacionDigital']]=$reg['Nombre'];
            } 
            //print_r($rangosxfirma);

            
            $Fechahoy=$param['Fechahoy'];
            $OpcionesList=array();
            
            $authNamespace = new Zend_Session_Namespace('Zend_Auth');
            $authNamespace->rangosxfirma=$rangosxfirma;
            $authNamespace->nombresxfirma=$nombresxfirma; 
            $firma=$authNamespace->firma;
            
            $Sqlstr="Select Codigo,Rango,Limite FROM DT02MOVI WHERE Codigo in ('PED','PEN','PEE','PMU')";
            $RegLimites=$this->mainmodel->sqlexec($Sqlstr);
            foreach ($RegLimites as $reg) {
               $indicerango= (int) $reg['Rango'];
               $limitesxrango[$reg['Codigo']][$indicerango]=$reg['Limite'];
            } 
            $authNamespace->limitesxrango=$limitesxrango; 
            //print_r($limitesxrango);
            
            $txtAlignRight=" text-align: right;";
            $txtAlignCenter=" text-align: center;";
            $nowrap=" white-space: nowrap;";
            
            $regs=$param['Row'];
            $table=  '<table class="table table-condensed table-hover" id="oblig" border="0" cellpadding="4" style="width:100%"><tr class="titulos">';
            $camposcol=array('Numero','TipoMoneda','MontoPedido','Cliente','FechaMov','FechaCompromiso','TotalPedidosCli','SaldoActual','SaldoVencido','TipoPago','DiasAprobado','EstadoTexto');
            if ($firma) {
               $camposcol[]='accion';
            }
            $camposcol[]='comment';
            $stylestitlerows=array($nowrap,'',$txtAlignRight,'',$txtAlignCenter,$txtAlignCenter,$txtAlignRight,$txtAlignRight,$txtAlignRight,$txtAlignCenter,$txtAlignRight,$txtAlignCenter.$nowrap,'',$txtAlignCenter); //aqui
            $camposFX=array('','','','','','','','','','','','','','');//aqui
            $labelscol=array( 'Pedido Nro.','','Monto','Cliente','Fecha', 'Compromiso','Tot.Pedidos','S.Actual','S.Vencido','Condic.Credito','Dias', 'Estado');
            if ($firma) {
               $labelscol[]='Firmar';
            }
              $labelscol[]='Observaciones';
            
            $stylescolrows=array($nowrap,'',$txtAlignRight,'',$txtAlignCenter,$txtAlignCenter,$txtAlignRight,$txtAlignRight,$txtAlignRight,$txtAlignCenter,$txtAlignRight,$txtAlignCenter.$nowrap,$txtAlignCenter,$txtAlignCenter); //aqui 
            $cont=0;
           
            foreach ($labelscol as $index => $label)  {
              if ($stylestitlerows[$index]) {
                     $styletext=' style="'.$stylestitlerows[$index].'"'; 
              } else {
                     $styletext='';  
              }
              $table.= "    <th$styletext>".$label.'</th>'; 
              $cont++;
            }
            $table.=  '</tr>';
            $TotalPedidos=0;
            $TotalAprob=0;
            if (count($regs)>0) {
              $modelgere=new Default_Model_Geredt03();
              $authNamespace = new Zend_Session_Namespace('Zend_Auth');
              $basedir='/files/datosadjuntos/'.str_pad($authNamespace->empresa, 3, "0", STR_PAD_LEFT).'/';
              $basedirdoc='/files/factprov/'.str_pad($authNamespace->empresa, 3, "0", STR_PAD_LEFT).'/';
              $SaldoActualCli=array();
              $SaldoVencidoCli=array();
              foreach ($regs as $reg)  {
                //$reg['Referenciatrim']=trim($reg['Referencia'],".,");  
                $id=$reg['CodMovimiento']."__".$reg['Referencia'];
                if ($reg['Estado']==1) {
                  $classTr=' class="aprobados '.$reg['CodClienteProveedor'].'"';//aprobados
                } else {
                  $classTr=' class="poraprobar '.$reg['CodClienteProveedor'].'"';
                }
                
                $table.=  '<tr id="'.$id.'"'.$classTr.">"; 
                reset($camposcol);
                
                $reg['Firmas']=is_null($reg['Firmas'])?0:$reg['Firmas'];
                
                $bitwise = $reg['Firmas'] & $firma;
                if ($bitwise) {//si ya esta firmado 
                  $titleimagen='Quitar Firma';  
                  $imagen='/img/desaprobar.jpg';
                  $funcion='aprobdesaprobPed('."0,'".$reg['CodMovimiento']."','".$reg['Referencia']."',".$reg['MontoPedido'].",".$reg['Estado'].",".$reg['PrePago'].");";
                } else {
                  $titleimagen='Firmar';   
                  $imagen='/img/aprobar.jpg';
                  $funcion='aprobdesaprobPed('."1,'".$reg['CodMovimiento']."','".$reg['Referencia']."',".$reg['MontoPedido'].",".$reg['Estado'].",".$reg['PrePago'].");";
                }
                $reg['accion']='<img title="'.$titleimagen.'" onclick="'.$funcion.'" src="'.$imagen.'" height="20" width="20">';
                $TotalPedidos+=$reg['MontoPedido'];
                if ($reg['Estado']==1) { 
                 $TotalAprob+=$reg['MontoPedido'];   
                 
                 
                } else {
                 
                 // $reg['accion']='<img onClick="'.$funcion.'" src="'.$imagen.'" height="20" width="20">';   
                } 
                
                $CommentRegs=$modelgere->getSelectWhereOrderSQL('Comentario',"Movimiento = '{$reg['CodMovimiento']}' AND Referencia = '{$reg['Referencia']}'");
                if (count($CommentRegs)>0) {
                   $ImageComment=trim($CommentRegs[0]['Comentario'])?'warning':'write';
                   $comment=trim($CommentRegs[0]['Comentario']);
                   $comment = str_replace(array("\r\n","\r","\n"),'\r', $comment);
                } else {
                   $ImageComment='write';
                   $comment='';
                }
                $Onclick="showComentario('$comment','{$reg['NombreClienteProveedor']}','{$reg['CodClienteProveedor']}','{$reg['CodMovimiento']}','{$reg['Referencia']}');";
                
                $reg['comment']='<img onClick="'.$Onclick.'" src="/img/'.$ImageComment.'.jpg" height="20" width="20">';
                
                
                
                $MonedaRef='$';
               
                
                $reg['Cliente']=substr($reg['NombreClienteProveedor'],0,30).'('.$reg['CodClienteProveedor'].')';
                
                if (!isset($SaldoActualCli[$reg['CodClienteProveedor']])) {
                    /*
                      $Sqlstr="select b.totalFacturas,b.totalPagos,b.totalFacturas-b.totalPagos as Saldo from (
                            select sum(a.debitos) as totalFacturas,sum(a.Creditos) as totalPagos from (
                            SELECT Facturas.Cliente, Facturas.TipoMovimiento,Facturas.Referencia, Facturas.TotalDocumento AS Debitos,       
                            (SELECT SUM(pagos.Monto) AS SumaDeMonto 
                            FROM DT01CRCL Pagos  WHERE pagos.Cliente = Facturas.Cliente  AND pagos.TipoDebito = Facturas.TipoMovimiento AND pagos.ReferenciaDebito = Facturas.Referencia      
                            GROUP BY pagos.Cliente) Creditos   
                            FROM EN01DECL AS Facturas  WHERE 
                            Facturas.Cliente = '{$reg['CodClienteProveedor']}') a) b"; 
                     */
                            /*
                       $Sqlstr="select b.totalFacturas,b.totalPagos,b.totalAnticipos,b.totalFacturas-b.totalPagos-b.totalAnticipos as Saldo from (
                            select sum(a.Debitos) as totalFacturas,sum(a.MontoAnticipos) as totalAnticipos,sum(a.Creditos) as totalPagos from (
                            SELECT Facturas.Cliente, Facturas.TipoMovimiento,Facturas.Referencia, Facturas.TotalDocumento AS Debitos,Facturas.MontoAnticipos,       
                            (SELECT isnull(Sum(CASE Header.Moneda WHEN '$MonedaRef' then pagos.Monto WHEN 'BS' then round(pagos.Monto/Header.FactorConversion,2) ELSE round(pagos.Monto*Header.FactorConversion,2)  END),0)
                            FROM DT01CRCL Pagos  JOIN EN01CRCL Header ON   Pagos.Cliente=Header.Cliente and Pagos.TipoMovimiento=Header.TipoMovimiento and Pagos.Referencia=Header.Referencia
							WHERE pagos.Cliente = Facturas.Cliente  AND pagos.TipoDebito = Facturas.TipoMovimiento AND pagos.ReferenciaDebito = Facturas.Referencia   
							  
                            GROUP BY pagos.Cliente) Creditos   
                            FROM EN01DECL AS Facturas  WHERE 
                            Facturas.Cliente = '{$reg['CodClienteProveedor']}'
                            AND Facturas.Fecha>='2016-01-01'
                            ) a) b";      
                            //SaldoActual
                            //sele arego lo de Facturas.Fecha>='2016-01-01' por caso Moliendas Papelon Andisacos
                             
                          $reg2=$this->mainmodel->sqlexec($Sqlstr);
                          $reg2=$reg2[0]; 
                          $reg['SaldoActual']=$reg2['Saldo'];
                             
                             * 
                             */
                            
                   //echo $Sqlstr.'<br>';
                    $modelDecl= new Cxcyp_Model_Declmodel();
                    $EsSaldoVencido=0;
                  
                    $reg['SaldoActual']=$modelDecl->getSaldoCliente($reg['CodClienteProveedor'],$EsSaldoVencido,$Fechahoy,0.1,'$','2016-01-01');
              //echo  '<pre>', print_r($reg2['Saldo']), '</pre>';
                  $SaldoActualCli[$reg['CodClienteProveedor']]=$reg['SaldoActual'];
                  /*
               $Sqlstr="select b.totalFacturas,b.totalPagos,b.totalFacturas-b.totalPagos as Saldo from (
                            select sum(a.debitos) as totalFacturas,sum(a.Creditos) as totalPagos from (
                            SELECT Facturas.Cliente, Facturas.TipoMovimiento,Facturas.Referencia, Facturas.TotalDocumento AS Debitos,       
                            (SELECT SUM(pagos.Monto) AS SumaDeMonto 
                            FROM DT01CRCL Pagos  WHERE pagos.Cliente = Facturas.Cliente  AND pagos.TipoDebito = Facturas.TipoMovimiento AND pagos.ReferenciaDebito = Facturas.Referencia      
                            AND pagos.Fecha <= '$Fechahoy'    
                            GROUP BY pagos.Cliente) Creditos   
                            FROM EN01DECL AS Facturas  WHERE 
                            Facturas.Vencimiento <= '$Fechahoy' AND 
                            Facturas.Cliente = '{$reg['CodClienteProveedor']}') a) b" ; 
                   * 
                   */
                       /*     
             $Sqlstr="select b.totalFacturas,b.totalPagos,b.totalAnticipos,b.totalFacturas-b.totalPagos-b.totalAnticipos as Saldo from (
                            select sum(a.Debitos) as totalFacturas,sum(a.MontoAnticipos) as totalAnticipos,sum(a.Creditos) as totalPagos from (
                            SELECT Facturas.Cliente, Facturas.TipoMovimiento,Facturas.Referencia, Facturas.TotalDocumento AS Debitos,Facturas.MontoAnticipos,       
                            (SELECT isnull(Sum(CASE Header.Moneda WHEN '$MonedaRef' then pagos.Monto WHEN 'BS' then round(pagos.Monto/Header.FactorConversion,2) ELSE round(pagos.Monto*Header.FactorConversion,2)  END),0)
                            FROM DT01CRCL Pagos  JOIN EN01CRCL Header ON   Pagos.Cliente=Header.Cliente and Pagos.TipoMovimiento=Header.TipoMovimiento and Pagos.Referencia=Header.Referencia
							WHERE pagos.Cliente = Facturas.Cliente  AND pagos.TipoDebito = Facturas.TipoMovimiento AND pagos.ReferenciaDebito = Facturas.Referencia   
			    AND pagos.Fecha <= '$Fechahoy'				  
                            GROUP BY pagos.Cliente) Creditos   
                            FROM EN01DECL AS Facturas  WHERE
                            Facturas.Vencimiento <= '$Fechahoy' AND
                            Facturas.Cliente = '{$reg['CodClienteProveedor']}'
                            AND Facturas.Fecha>='2016-01-01'
                            ) a) b";      //SaldoVencido
                   
                
                          
                  $reg2=$this->mainmodel->sqlexec($Sqlstr);
                  $reg2=$reg2[0];
                  $reg['SaldoVencido']=$reg2['Saldo'];
                  //echo '<pre>',print_r($reg['SaldoVencido']),'</pre>';
                  $SaldoVencidoCli[$reg['CodClienteProveedor']]=$reg2['Saldo'];
                        * 
                        */
                      $EsSaldoVencido=1;
                      $reg['SaldoVencido']=$modelDecl->getSaldoCliente($reg['CodClienteProveedor'],$EsSaldoVencido,$Fechahoy,0.1,'$','2016-01-01');
                      $SaldoVencidoCli[$reg['CodClienteProveedor']]=$reg['SaldoVencido'];
                } else {
                   $reg['SaldoActual']=$SaldoActualCli[$reg['CodClienteProveedor']];
                   $reg['SaldoVencido']=$SaldoVencidoCli[$reg['CodClienteProveedor']];
                }
                
                $reg['Numero']=$reg['CodMovimiento'].'-'.$reg['Referencia'].'<br><img src="/img/lupa.jpg" height="20" width="20">';
                //$reg['Proveedor']=substr($reg['Proveedor'],0,20);
                $reg['FechaMov']=$this->mainmodel->SQLtoLatin($reg['FechaMov']);
                $reg['FechaCompromiso']=$this->mainmodel->SQLtoLatin($reg['FechaCompromiso']);
                //modificacion 30/04/2018 - cambios modulo gerencia aprobacion ver servicios
                //$reg['MontoPedido']=$reg['MontoPedido']+$reg['MontoPedidoServ'];//////agregar al sistema en prod////////////////////////////////////////////////
                $reg['MontoPedido']=$this->mainmodel->formatDec2($reg['MontoPedido']);
                //modificacion 30/04/2018 - cambios modulo gerencia aprobacion ver servicios
                //$reg['TotalPedidosCli']=$reg['TotalPedidosCliServ']+$reg['TotalPedidosCli']; //////agregar al sistema en prod////////////////////////////////////////////////
                $reg['TotalPedidosCli']=$this->mainmodel->formatDec2($reg['TotalPedidosCli']);
                $reg['SaldoActual']=$this->mainmodel->formatDec2($reg['SaldoActual']);
                $reg['SaldoVencido']=$this->mainmodel->formatDec2($reg['SaldoVencido']);
                $reg['PrePago']=($reg['PrePago']==1 and $reg['MontoPedido']>0)?1:0;
                //echo "{$reg['Referencia']} => {$reg['PrePago']}<br>";
                $arrEstado=$this->DeterminarEstadoPedidos($reg['Firmas'],$reg['CodMovimiento'], $reg['Referencia'],$reg['PrePago']);
                $reg['EstadoTexto']=$arrEstado['TextoEstado'];
                //$reg['FechaVeSC']=$this->mainmodel->SQLtoLatin($reg['FechaVeSC']);
                
                if (($reg['EstadoTexto']<>'Aprobado') and ($reg['Estado']==1)) {
                    $reg['EstadoTexto']='Aprobado*';//marca los que fueron aprobados y no cumplen regla de aprobacion
                }
                $Separador='&#13;';
                $tiranombres=str_replace($Separador,'<br>',$arrEstado['TiraNombres']);
                $TitlePopOver="Firmas ".$reg['CodMovimiento'].'-'.$reg['Referencia'];
                $IdPopOver="A".$reg['CodMovimiento'].'-'.$reg['Referencia'];
                $reg['EstadoTexto']='<a class="anchor" id="'.$IdPopOver.'"></a><a class="uniclass" href="#'.$IdPopOver.'"  data-toggle="popover" title="'.$TitlePopOver.'" data-content="'.$tiranombres.'">'.$reg['EstadoTexto'].'</a>';
                $textoProv=$reg['Referencia'];
                //$reg['showodc']=is_null($reg['RefODC'])?'':$reg['CodMovimiento'].'-'.$reg['RefODC'].($reg['LinkCt']?'<img onClick="{window.open('."'/files/datosadjuntos/001/OMM-19215-SP.pdf', '_blank');}".'" src="/img/contract.jpg" height="20" width="20">':'');
                foreach ($camposcol as $index=>$campo)  {
                    
                  if ($stylescolrows[$index]) {
                     if (($campo=='SaldoVencido' and $reg['SaldoVencido']>0) //OR ($campo=='SaldoActual' and $reg['SaldoActual']>0)
                             ) { 
                      $styletext=' style="'.$stylescolrows[$index].'  font-weight: bold; color: #FF0000;"';     
                     } else {
                      $styletext=' style="'.$stylescolrows[$index].'"'; 
                     }
                  } else {
                     $styletext='';  
                  }
                  
                  if ($campo=='EstadoTexto') {
                     $title=' title="'.$arrEstado['TiraNombres'].'"'; 
                  } elseif ($campo=='Numero') {
                     $title=' title="'.$reg['CodMovimiento'].'-'.$reg['Referencia'].'"';  
                  }  else {
                     $title=''; 
                  }
                  
                  if ($campo=='showdoc') {
                    $idcell=' id="imgdoc"';    
                  } elseif ($campo=='accion') {
                    $idcell=' id="imgaccion"';
                  } elseif ($campo=='MontoAprobado') {
                    $idcell=' id="maprob"';  
                  } elseif ($campo=='EstadoTexto') {
                    $idcell=' id="txtcond"'; 
                    } elseif ($campo=='comment') {
                    $idcell=' id="comment"';   
                  } else {
                    $idcell='';  
                  }
                  
                  $this->ModalAdic=$this->view->getHelper('flashModalTextoPed')->flashModalTextoPed('Comentario','commentModal');
                  
                  if (substr($camposFX[$index],0,2)=='CM') {
                    $classTdFx=' class="'.$camposFX[$index].'"';
                    $attribTdFx=' base="'.$reg[$campo.'Base'].'"';
                  } else {
                    $classTdFx='';
                    $attribTdFx='';
                  }
                  
                  if ($campo=='Numero') {
                    $NombreCliente=trim(substr($reg['NombreClienteProveedor'],0,25));  
                    /* cambio realizado por Pablo Sanchez 13/06/2018
                     * original:  
                    $Onclick='onClick="'."showPedidos('".$NombreCliente."','".$reg['CodMovimiento']."','".$reg['Referencia']."');".'"'; 
                    */
                    //$Onclick='onClick="'."showPedidosdesdeaprob('".$NombreCliente."','".$reg['CodMovimiento']."','".$reg['Referencia']."','".$reg['representante']."');".'"'; 
                    //P-CargarLista
                    $Onclick='onClick="'."showPedidosdesdeaprob('".$NombreCliente."','".$reg['CodMovimiento']."','".$reg['Referencia']."','".$reg['representante']."','".$authNamespace->empresa."','".$reg['Tipo']."');".'"'; 
                   } elseif ($campo=='Cliente') {
                    $Onclick=' onClick="filtraXcliente('."'".$reg['CodClienteProveedor']."'".');"'; 
                  } else {
                    $Onclick='';  
                  }
                  //auxFunction='showDespachos("'+data[i].CodMovimiento+'","'+data[i].Referencia+'")';
                  /*
                  if ($campo=='Referencia') {
                    $reg['Referencia']=$reg['Referencia'].' '.$reg['showdoc'];
                  }
                   * 
                   */
                  
                  
                  $table.=  "<td$title$styletext$idcell$classTdFx$attribTdFx$Onclick>".$reg[$campo].'</td>';  
                }
                 
                $table.=  '</tr>';
              }
            } else {
               $table.= '<tr><td colspan="'.$cont.'">'.'NO EXISTEN PEDIDOS PARA APROBACION'.'</td></tr>'; 
            }
            $table.= '</table>';
            echo $table;
              
            //OJO NO PERO ALGO SIMILAR $OpcionesList['Moneda'] = $this->mainmodel->getSelectWhereOrderMultiCol($cond,'Codigo', 'Descripcion', 'Descripcion',' - ',array(),25,array('Descripcion','Rif','Nit','Direccion','Telefonos','CodigoContableCl','CodigoContablePr','Cliente','Proveedor')); 
            
            
            
            
            $model1=new Inventario_Model_Monemodel();
            $OpcionesList['TipoMoneda']=$model1->getUltCotizPeriodo(); 
            $OpcionesList['Filtro']=array('Todos','Aprobados','Por Aprobar'); 
            $OpcionesList['TotalPedidos']=$TotalPedidos;
            $OpcionesList['TotalAprob']=$TotalAprob;
            $OpcionesList['Firma']=$firma;
        //echo '**=<pre>',print_r($OpcionesList,1),'</pre>';
           
           $this->ModalAdic=$this->view->getHelper('flashModalFooter')->flashModalFooter('','artiuniModal').
               $this->view->getHelper('flashModalTextoPed')->flashModalTextoPed('Comentario','commentModal');
           
           
           
          return $OpcionesList;
          
        }  
    public function aprobAction()         
        {
          $Fechahoy=date('Y-m-d');
          $param['Fechahoy']=$Fechahoy;
          /*$sqlstr="SELECT a.CodMovimiento, a.Referencia, a.FechaMov, c.FechaCompromiso,  a.CodClienteProveedor, a.NombreClienteProveedor, a.TipoPago, a.Estado, a.Firmas, 
a.CodVendedor,  a.FechaFirma, b.Denominacion, a.TipoMoneda, b.Cedula, b.representante, CASE WHEN a.Estado=1 then DATEDIFF(day,a.FechaFirma,'$Fechahoy') else null end AS DiasAprobado,
          (select sum(t.Cantidad*t.PreUnitario) from dt01fact t where t.CodMovimiento=a.CodMovimiento and t.Referencia=a.Referencia) as MontoPedido,
(select sum(b1.Cantidad*b1.PreUnitario) from en01fact a1 join dt01fact b1 on a1.CodMovimiento=b1.CodMovimiento
and a1.Referencia=b1.Referencia JOIN MT01MOVI c1 ON a1.CodMovimiento = c1.Codigo
where (a1.CodClienteProveedor=a.CodClienteProveedor) and ((a1.Estado=0) or (a1.Estado=1)) and (c1.EsAprobable=1) and (c1.CompraVenta=1)) as TotalPedidosCli
FROM (EN01FACT a JOIN DT08FACT c ON a.CodMovimiento = c.CodMovimiento and a.Referencia=c.Referencia  JOIN MT01MOVI ON a.CodMovimiento = MT01MOVI.Codigo)        
INNER JOIN EN01CLIE b  ON a.CodClienteProveedor = b.codigo
 WHERE (((a.Estado)=0 Or (a.Estado)=1)    AND ((MT01MOVI.EsAprobable)=1) AND ((MT01MOVI.CompraVenta)=1)) 
 ORDER BY  a.Referencia, a.FechaMov DESC";//*/ //modificacion 30/04/2018 - cambios modulo gerencia aprobacion ver servicios
          //modificacion 30/04/2018 - cambios modulo gerencia aprobacion ver servicios:
          $modelVargen=new Default_Model_Defimodel;
          $vargen = $modelVargen->getSelectWhereOrderSQL('DesactPedAuto,DiasRegPedido','1=1', '');
          $vargen=$vargen[0];
          if ($vargen['DesactPedAuto']) { //Desactivacion Automatica de Pedidos
            $SqlUpd="update a
            set a.Estado=2
            from en01fact a join dt08fact b on a.CodMovimiento=b.CodMovimiento and a.Referencia=b.Referencia
            where (a.CodMovimiento in (select Codigo from mt01movi where CompraVenta=1 and AfectaExistencias=2 and DestinoMovimiento=0))
            and (DATEDIFF(day,a.FechaMov,'$Fechahoy')>{$vargen['DiasRegPedido']})
            and (
            (a.Estado=0) OR
            ((a.Estado=1) AND (DATEDIFF(day,b.FechaCompromiso,'$Fechahoy')>0)
            and (select count(*) from en01fact where CodMovimiento in (select Codigo from mt01movi where CodOtroMovimiento in (select Codigo from mt01movi where CompraVenta=1 and AfectaExistencias=2 and DestinoMovimiento=0)) and MovReferenciado=a.Referencia)=0
            )
            )";
            $rslt=$this->mainmodel->sqlqry($SqlUpd);
          }
          
          /*
         $sqlstr="SELECT a.CodMovimiento, a.Referencia, a.FechaMov, c.FechaCompromiso,  a.CodClienteProveedor, a.NombreClienteProveedor, (SELECT CASE WHEN Formula='0' THEN 1 ELSE 0 END FROM mt01tipa WHERE Codigo=a.TipoPago)   as PrePago ,concat(a.TipoPago,'<br>(',p.Descripcion,')') as TipoPago, a.Estado, a.Firmas, 
a.CodVendedor,  a.FechaFirma, b.Denominacion, b1.Tipo, a.TipoMoneda, b.Cedula, b.representante, CASE WHEN a.Estado=1 then DATEDIFF(day,a.FechaFirma,'$Fechahoy') else null end AS DiasAprobado,
          (select sum(t.Cantidad*t.PreUnitario) from dt01fact t where t.CodMovimiento=a.CodMovimiento and t.Referencia=a.Referencia) as MontoPedido,
          (select sum(ts.Cantidad*ts.PrecioUnitario) from dt03fact ts where ts.CodMovimiento=a.CodMovimiento and ts.Referencia=a.Referencia) as MontoPedidoServ,
(select sum(b1.Cantidad*b1.PreUnitario) from en01fact a1 join dt01fact b1 on a1.CodMovimiento=b1.CodMovimiento
and a1.Referencia=b1.Referencia JOIN MT01MOVI c1 ON a1.CodMovimiento = c1.Codigo
where (a1.CodClienteProveedor=a.CodClienteProveedor) and ((a1.Estado=0) or (a1.Estado=1)) and (c1.EsAprobable=1) and (c1.CompraVenta=1)) as TotalPedidosCli,
(select sum(ts.Cantidad*ts.PrecioUnitario) from en01fact aa1 join dt03fact ts on aa1.CodMovimiento=ts.CodMovimiento and aa1.Referencia=ts.Referencia JOIN MT01MOVI cc1 ON aa1.CodMovimiento = cc1.Codigo
where (aa1.CodClienteProveedor=a.CodClienteProveedor) and ((aa1.Estado=0) or (aa1.Estado=1)) and (cc1.EsAprobable=1) and (cc1.CompraVenta=1)) as TotalPedidosCliServ
FROM (EN01FACT a JOIN DT08FACT c ON a.CodMovimiento = c.CodMovimiento and a.Referencia=c.Referencia  JOIN MT01MOVI ON a.CodMovimiento = MT01MOVI.Codigo)        
INNER JOIN EN01CLIE b  ON a.CodClienteProveedor = b.codigo INNER JOIN DT05CLIE b1  ON a.CodClienteProveedor = b1.codigo
inner join mt01tipa p ON a.TipoPago=p.Codigo
 WHERE (((a.Estado)=0 Or (a.Estado)=1)    AND ((MT01MOVI.EsAprobable)=1) AND ((MT01MOVI.CompraVenta)=1)) 
 ORDER BY  a.CodMovimiento DESC,a.Referencia DESC, a.FechaMov DESC";
           * 
           */
         
         
         $sqlstr="SELECT a.CodMovimiento, a.Referencia, a.FechaMov, c.FechaCompromiso,  a.CodClienteProveedor, a.NombreClienteProveedor, (SELECT CASE WHEN Formula='0' THEN 1 ELSE 0 END FROM mt01tipa WHERE Codigo=a.TipoPago)   as PrePago ,concat(a.TipoPago,'<br>(',p.Descripcion,')') as TipoPago, a.Estado, a.Firmas, 
a.CodVendedor,  a.FechaFirma, b.Denominacion, b1.Tipo, a.TipoMoneda, b.Cedula, b.representante, CASE WHEN a.Estado=1 then DATEDIFF(day,a.FechaFirma,'$Fechahoy') else null end AS DiasAprobado,
          (select sum(t.Cantidad*t.PreUnitario) from dt01fact t where t.CodMovimiento=a.CodMovimiento and t.Referencia=a.Referencia) as MontoPedido,
          (select sum(ts.Cantidad*ts.PrecioUnitario) from dt03fact ts where ts.CodMovimiento=a.CodMovimiento and ts.Referencia=a.Referencia) as MontoPedidoServ,
(select sum(b1.Cantidad*b1.PreUnitario) from en01fact a1 join dt01fact b1 on a1.CodMovimiento=b1.CodMovimiento
and a1.Referencia=b1.Referencia JOIN MT01MOVI c1 ON a1.CodMovimiento = c1.Codigo
where (a1.CodClienteProveedor=a.CodClienteProveedor) and ((a1.Estado=0) or (a1.Estado=1)) and (c1.EsAprobable=1) and (c1.CompraVenta=1)) as TotalPedidosCli,
(select sum(ts.Cantidad*ts.PrecioUnitario) from en01fact aa1 join dt03fact ts on aa1.CodMovimiento=ts.CodMovimiento and aa1.Referencia=ts.Referencia JOIN MT01MOVI cc1 ON aa1.CodMovimiento = cc1.Codigo
where (aa1.CodClienteProveedor=a.CodClienteProveedor) and ((aa1.Estado=0) or (aa1.Estado=1)) and (cc1.EsAprobable=1) and (cc1.CompraVenta=1)) as TotalPedidosCliServ
FROM (EN01FACT a JOIN DT08FACT c ON a.CodMovimiento = c.CodMovimiento and a.Referencia=c.Referencia  JOIN MT01MOVI ON a.CodMovimiento = MT01MOVI.Codigo)        
INNER JOIN EN01CLIE b  ON a.CodClienteProveedor = b.codigo INNER JOIN DT05CLIE b1  ON a.CodClienteProveedor = b1.codigo
inner join mt01tipa p ON a.TipoPago=p.Codigo
          LEFT JOIN (select a.Banco,b.TipoDebito,b.ReferenciaDebito from en01mvsl a join dt03mvsl b on a.Banco=b.Banco and a.Referencia=b.Referencia and a.Movimiento=b.Movimiento
 where a.GrupoEntidad='CLIE' and b.TipoDebito in (select Codigo from mt01timo where DocuDeducible=1 and CuentasPorCobrar=1)
 ) z 
 ON z.ReferenciaDebito in (a.Referencia,concat(a.Referencia,'-B'))
 WHERE (((a.Estado)=0 Or (a.Estado)=1)    AND ((MT01MOVI.EsAprobable)=1) AND ((MT01MOVI.CompraVenta)=1)  AND Z.Banco is null) 
 ORDER BY  a.CodMovimiento DESC,a.Referencia DESC, a.FechaMov DESC";
          
          //echo "$sqlstr";
          $Datos=$this->mainmodel->sqlexec($sqlstr);
                    
                    //$Datos=$Datos->toArray();
          $param['Row']=$Datos;
                    
          $param['OptionsList']=$this->asignaOpcionesForm($param);
          $form= new $this->mainform();
          $form->LoadForm($param);
                    
                    
          $Datos=$this->modDatosForm($this->ModValuesEdit($param),$Datos);
          $form->populate($Datos);
          $this->view->adicModal=isset($this->ModalAdic)?$this->ModalAdic:'';
          $this->view->form = $form;
          $this->view->Titulo = $this->titulo;
          //$this->view->layout()->scriptTags = $this->scriptTagsBase .$this->scriptTagsAdd; 
          $this->view->layout()->scriptTags = $this->scriptTagsAdd;
          if ($this->usarviewbase480) {$this->renderScript('base480/aprob.phtml');}// OJO SE COLOCA DE ULTIMO
          
        } 
    public function BeforeEdit()         
        { 
         parent::BeforeEdit();
         
         return 1;   
        }
        
    public function ModValuesEdit($param=array())         
        {
         $resultarray=array();
         $qry=$param['Row'];
         $resultarray['Fecha']=date('d/m/Y');
         $FechaHoy=$param['Fechahoy'];
         $FechaAyer=date('Y-m-d',strtotime('-1 day',strtotime($FechaHoy)));
         
        
         
         $resultarray['HTotalPedidos']=$param['OptionsList']['TotalPedidos'];
         $resultarray['TotalPedidos']=$this->mainmodel->formatDec2($param['OptionsList']['TotalPedidos']);
         
         $resultarray['HTotalAprob']=$param['OptionsList']['TotalAprob'];
         $resultarray['TotalAprob']=$this->mainmodel->formatDec2($param['OptionsList']['TotalAprob']);
         
         $auth = Zend_Auth::getInstance();
         $resultarray['Usuario']=$auth->getIdentity();
         $resultarray['Ipaddress']=$_SERVER['REMOTE_ADDR'];
         $resultarray['Firma']=$param['OptionsList']['Firma'];
         return $resultarray;
        }  
        
     
        
         
        
    public function upddrprAction()
        {
            
           if($this->getRequest()->isPost() ) {
                 
                $ActivarPrepago=$this->ActivarPrepago;
                
                $modo=$this->_getParam('modo');
                $codmov=$this->_getParam('codmov');
                $referencia=$this->_getParam('referencia');
                $monto=$this->_getParam('monto');
                //$firmas=$this->_getParam('firmas');
                $estado=$this->_getParam('estado');
                $estado=$estado?1:0;
                $usuario=$this->_getParam('usuario');
                $ipaddress=$this->_getParam('ipaddress');
                $esprepago=($this->_getParam('esprepago')==1 and $monto>0)?1:0; //Pedidos de Muestra (monto==0) no requieren de pago alguno
                
                
                
                
                $computername='PHP';
                
                
                 
                $authNamespace = new Zend_Session_Namespace('Zend_Auth');
                $firma=$authNamespace->firma;
                
                $Sqlstr="SELECT Firmas,CodClienteProveedor from en01fact WHERE CodMovimiento='$codmov' and Referencia='$referencia'";
                $RegFirma=$this->mainmodel->sqlexec($Sqlstr);
                if (count($RegFirma)>0) {
                  $firmas=is_null($RegFirma[0]['Firmas'])?0:$RegFirma[0]['Firmas'];
                  $codcliente=$RegFirma[0]['CodClienteProveedor'];
                } else {
                  //NO EXISTE EL PEDIDO?  
                  $firma=0;  
                }
                
                $arrEstadoComienzo=$this->DeterminarEstadoPedidos($firmas,$codmov,$referencia,$esprepago);
                $textoInicialEstado=$arrEstadoComienzo['TextoEstado'];
                
                if($firma){
                    
                    
                    $bitwise = $firmas & $firma;
                    if ($bitwise) {//si ya fue firmado
                        $quitoFirma=1;
                        $firmas-=$firma;
                        $img='/img/aprobar.jpg';
                        $arrEstado=$this->DeterminarEstadoPedidos($firmas,$codmov,$referencia,$esprepago);
                        $textoAprob=$arrEstado['TextoEstado'];
                        $aprobado=(substr($textoAprob,0,1)=='A')?1:0;
                        $funcion="aprobdesaprobPed(1,'$codmov','$referencia',$monto,$aprobado,$esprepago);";
                    } else {
                        $quitoFirma=0;
                        $firmas+=$firma;
                        $img='/img/desaprobar.jpg';
                        $arrEstado=$this->DeterminarEstadoPedidos($firmas,$codmov,$referencia,$esprepago);
                        $textoAprob=$arrEstado['TextoEstado'];
                        $aprobado=(substr($textoAprob,0,1)=='A')?1:0;
                        $funcion="aprobdesaprobPed(0,'$codmov','$referencia',$monto,$aprobado,$esprepago);";
                    }
                    
                    
                    
                    if ($estado<>$aprobado) {  //si hubo cambio de estado de 0 a 1 o de 1 a 0
                        if ($aprobado==1) {//Aprobar
                            $montoaprob=$monto;
                            $netoaprob=$monto;
                            $Fechahoy=date('Y-m-d');
                            $Sqlstr="UPDATE en01fact SET Estado=1,Firmas=$firmas,FechaFirma='$Fechahoy' WHERE CodMovimiento='$codmov' and Referencia='$referencia'";
                            $Result=$this->mainmodel->sqlqry($Sqlstr);
                            $clasetr='aprobados';
                            
                        } else { //Desaprobar
                            $montoaprob=0;
                            $netoaprob=-$monto;
                            $Sqlstr="UPDATE en01fact SET Estado=0,Firmas=$firmas,FechaFirma=null WHERE CodMovimiento='$codmov' and Referencia='$referencia'";
                            $Result=$this->mainmodel->sqlqry($Sqlstr);
                            $clasetr='poraprobar';
                            
                        }
                    } else {  //si no hubo cambio de estado
                       $montoaprob=0; 
                       $netoaprob=0;
                       $Sqlstr="UPDATE en01fact SET Firmas=$firmas WHERE CodMovimiento='$codmov' and Referencia='$referencia'";
                       $Result=$this->mainmodel->sqlqry($Sqlstr);
                       $clasetr='';
                       if ($ActivarPrepago) {
                            if ($esprepago and !$quitoFirma and substr($textoAprob,0,1)=='P') { // si es prepago y firmo y nuevo estado=pago pendiente
                                    include_once('/generacuentas.php');
                                    $arrResult['ResultAnticipo']=CreateAnticipos($codmov, $referencia, $codcliente);
                            } elseif ($esprepago and $quitoFirma and substr($textoInicialEstado,0,1)=='P') { // si es prepago quito firma y estado inicial era P
                                    include_once('/generacuentas.php');
                                    DeleteAnticipos($codmov, $referencia, $codcliente);
                            }
                       }
                    } 
                    //http://stackoverflow.com/questions/14777324/how-to-return-only-json-from-zend
                    //otras formas
                    /*
                    $Sqlstr="UPDATE en01depr SET MontoAprobado=$montoaprob,EstadoDocumento=$Estado WHERE Cliente='' and TipoMovimiento='$tipomov' and Referencia='$referencia'";
                    //$Result=$this->mainmodel->sqlqry($Sqlstr);
                    
                    // log
                    $fecha=date('Y-m-d');
                    $fechahora=date('Y-m-d H:i:s');
                    $Sqlstr="INSERT INTO dt03depr (Cliente,Tipomovimiento,Referencia,Usuario,Firma,Fecha,Hora,Accion,IpAdress,ComputerName) values ('','$tipomov','$referencia','$usuario',$firma,'$fecha','$fechahora','$accion','$ipaddress','$computername')";
                    //$Result=$this->mainmodel->sqlqry($Sqlstr); 
                    //$arrResult['Sql']=$Sqlstr;
                    // fin log
                     * 
                     */
                    $arrResult['ClaseTr']=$clasetr;
                    $arrResult['Img']=$img;
                    $arrResult['Fcn']=$funcion;
                    $arrResult['Txt']=$textoAprob;
                    $arrResult['MApr']=$montoaprob;
                    $arrResult['NetoApr']=$netoaprob;
                    $Separador='&#13;';
                    $arrResult['FirmasHtml']=utf8_encode(str_replace($Separador,'<br>',$arrEstado['TiraNombres']));
                    $a=explode($Separador,$arrEstado['TiraNombres']);
                    $c='';
                    $count=0;
                    foreach($a as $b) {
                       $b=utf8_encode($b);
                       if ($count==0) {
                        $c=$b;   
                       } else {
                        $c.="
$b"; 
                       }
                       $count++;
                    }
                    $arrResult['TiraFirmas']=$c;//lo que hay que inventar ya que '&#13;' no se convierte en line break 
                    //al modificar el atrib via javascript
                    
                    $arrResult['SinFirma']=0;
                   
                } else {
                    $arrResult['SinFirma']=1;
                }
                 $this->_helper->json($arrResult);
            }
        }
     
     
     public function DeterminarEstadoPedidos($Firmas, $CodMovimiento, $Referencia, $Esprepago)
        {
            $ActivarPrepago=$this->ActivarPrepago;
            $paramEmpresas=array(1=>array('medida'=>'KG'),2=>array('medida'=>'MTS2'),6=>array('medida'=>'MTS2'));
            $authNamespace = new Zend_Session_Namespace('Zend_Auth');
            $empresaActual =$authNamespace->empresa;
            $rango=$authNamespace->rango;
            //print_r($rango);
            $rangosxfirma=$authNamespace->rangosxfirma;
            $nombresxfirma=$authNamespace->nombresxfirma;
            $limitesxrango=$authNamespace->limitesxrango;
            $limitesxrango=$limitesxrango[$CodMovimiento];
            //print_r($limitesxrango);
            
            $UnidadSuma=$paramEmpresas[$empresaActual]['medida'];
            $strSql = "SELECT Sum(DT05FACT.Cantidad) AS SumaUnidad FROM DT05FACT WHERE (((DT05FACT.CodMovimiento)='$CodMovimiento') AND ((DT05FACT.Referencia)='" . $Referencia . "') AND ((DT05FACT.UnidadMedida)='" . $UnidadSuma . "'))";
            $TotalPedido=$this->mainmodel->sqlexec($strSql);
            if (count($TotalPedido)>0) {
               $SumaUnidPedido=$TotalPedido[0]['SumaUnidad'];
            } else {
               $SumaUnidPedido=0; 
            }
            $Puntos = 0;
            $chorizo=sprintf('%1$04b', $Firmas);
            $lonchorizo=strlen($chorizo);
            //echo "PEDIDO UNIDADES=$SumaUnidPedido<br>";
            //echo $chorizo.' len='.$lonchorizo.'<br>';
            $nombresfirma=array();
            for ($i = 0; $i <= ($lonchorizo-1); $i++) {
                if (substr($chorizo,$i,1)=='1') {//por cada firma registrada en el pedido
                  $exponente=$lonchorizo-$i-1;
                  $FirmaEval=pow(2,$exponente);
                  $nombresfirma[]=isset($nombresxfirma[$FirmaEval])?$nombresxfirma[$FirmaEval]:'';
                  $rangoEval=isset($rangosxfirma[$FirmaEval])?$rangosxfirma[$FirmaEval]:1;
                  $rangoEval=($rangoEval>3)?3:$rangoEval;
                  $PuntosRango=4-$rangoEval;
                  $Limite=isset($limitesxrango[$rangoEval])?$limitesxrango[$rangoEval]:0;
                  if (($rangoEval==2) and ($Limite >= $SumaUnidPedido)) { //superpoderes
                     $PuntosRango+=1; 
                  }
                  //echo "firma=$valorEval rango=$rangoEval limite=$Limite ptosrango=$PuntosRango<br>";
                  $Puntos+=$PuntosRango;
                } 
            }
            $Separador='&#13;';
            if (count($nombresfirma)>0) {
              $devolver['TiraNombres']=implode($Separador,$nombresfirma);
            } else {
              $devolver['TiraNombres']='Sin firmas';  
            }
            //echo "PtosS=$Puntos<br>";
            if ($Puntos==0) {
              $devolver['TextoEstado']= 'Sin Revisar';  
            } elseif ($Puntos==1) {
              $devolver['TextoEstado']= 'Revisado';  
            } elseif ($Puntos<=3) {
              $devolver['TextoEstado']= 'Falta 1 Firma'; 
            } elseif ($ActivarPrepago and $Esprepago) {
               $devolver['TextoEstado']= 'Pago Pendiente'; 
            } else {
              $devolver['TextoEstado']= 'Aprobado';    
            }
            return $devolver;
        }   
     public function cargararticulosAction()
        {
            
           if($this->getRequest()->isPost() ) {
                
                $CodMovimiento=$this->_getParam('tipMov');
                $Referencia=$this->_getParam('ref');
                    $arrArticulos=$this->mainmodel->getArticulos($CodMovimiento,$Referencia);
                    $this->_helper->json($arrArticulos);
            }
        } 
      
      public function cargarunidadesAction()
        { 
          
            if($this->getRequest()->isPost() ) {
                
                $CodMovimiento=$this->_getParam('tipMov');
                $Referencia=$this->_getParam('ref');
                $CodArticulo=$this->_getParam('codArt');
                $arrUnidades=$this->mainmodel->getUnidades($CodMovimiento,$Referencia,$CodArticulo);
                $this->_helper->json($arrUnidades);
            } 
          
        }  
         public function guardarcommentAction()
        { 
          
            if($this->getRequest()->isPost() ) {
                
                $NomProv=$this->_getParam('NomProv');
                $Cliente=$this->_getParam('Prov');
                $Movimiento=$this->_getParam('Movimiento');
                $Referencia=$this->_getParam('Referencia');
                //$Comentario=$this->_getParam('Comentario');
                $model=new Default_Model_Geredt03();
                $datos['Movimiento']=$Movimiento;
                $datos['Referencia']=$Referencia;
                $Comentario=(string) substr(trim($this->_getParam('Comentario')),0,250);
                $datos['Comentario']= utf8_decode($Comentario);
                $model->addupdateGenFast(array('Movimiento','Referencia','Comentario'),$datos,array($Movimiento,$Referencia));
            
                $img=$datos['Comentario']?'/img/warning.jpg':'/img/write.jpg';
                $comment = str_replace(array("\r\n","\r","\n"),'\r', $Comentario);
                $funcion="showComentario('$comment','$NomProv','$Cliente','$Movimiento','$Referencia');";
                $arrResult['Result']=1;
                $arrResult['Img']=$img;
                $arrResult['Fcn']=$funcion;
                $this->_helper->json($arrResult);
            } 
          
        }
      
        
        
        
      
       
}




?>
