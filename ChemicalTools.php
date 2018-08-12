<?php
if (!count(debug_backtrace())){
    if(isset($_GET["input"])){
        $tools=new ChemicalTools;
        echo $tools->processinput($_GET["input"]);
    }
}

class ChemicalTools{
	public function welcome(){
		return "欢迎使用化学e+，化学e+支持元素查询、计量计算、酸碱计算、气体计算、偏差计算等功能。您可以输入元素名称/符号/原子序数/IUPAC名查询元素，也可以输入化学式计算分子量，或者输入一组数据计算其偏差。详细帮助请输入help查询。";
	}
	public function gethelp(){
		return "化学e+支持以下功能：\n".
			"1.元素查询\n输入元素名称/符号/原子序数/IUPAC名查询元素，输入“元素”查看所有元素。\n示例：72\n示例：Hafnium\n".
			"2.质量计算\n输入化学式计算分子量。\n示例：(NH4)6Mo7O24\n".
			"3.酸碱计算\n输入HA（或HB） 分析浓度 pKa（或pKb）计算溶液成分。\n示例：HA 0.1 2.148 7.198 12.319\n".
			"4.气体计算\n输入未知量（p，V，n，T），并依次输入p，V，n，T中的已知量，即可计算未知量。\n示例：n 101 1 298\n".
			"5.偏差计算\n输入一组数据计算其偏差（用空格间隔）。\n示例：0.3414 0.3423 0.3407\n";	
	}
	public function processinput($input){
		if(!empty($input)){
			$arr=explode(" ",trim($input));
			$t=count($arr);
			if($t>1){
				for($i=0;$i<$t;$i++)$arr[$i]=trim($arr[$i]);
				if($t>2&&(strtoupper($arr[0])=="HA"||strtoupper($arr[0])=="BOH")){
					//Acid
					$output=$this->calculateacid($arr[0],(double)$arr[1],array_slice($arr,2));
				}else if($t==4&&(strtolower($arr[0])=="p"||strtolower($arr[0])=="v"||strtolower($arr[0])=="n"||strtolower($arr[0])=="t")){
					//gas
					$output=$this->calculategas($arr[0],$arr[1],$arr[2],$arr[3]);	
				}else{
					//deviation
					$output=$this->calcatedeviation($arr);
				}
			}else{
				if(strpos(strtolower($input),"help")!== false||strpos($input,"帮助")!== false){
					//help
					$output=$this->gethelp();
				}else if(strpos(strtolower($input),"welcome")!== false){
					//welcome
					$output=$this->welcome();
				}else if(strpos($input,"元素")!== false){
					//element table
					$output=$this->getelementtable();
				}else{
					//search element
					$output=$this->getelementinfo($input);
					if(!$output)$output=$this->calculatemass($input);
					if(!$output)$output="输入错误！";
				}
			}
		}else{
			$output="请输入内容！";
		}
		return $output;
	}
	private function searchelement($input){
		require_once('element_xml.php');
		require_once('pinyin.php');
		$elementNameArray=getinfo("name");
		$elementAbbrArray=getinfo("abbr");
		$elementIUPACArray=getinfo("iupac");
		$elementpinyin=getinfo("pinyin");
		for($i=0;$i<count($elementNameArray);$i++) {
			if ($input==(string)($i+1)||$input==($elementNameArray[$i])||strtolower($input)==strtolower($elementAbbrArray[$i])||strtolower($input)==strtolower($elementIUPACArray[$i])||pinyin($input)==$elementpinyin[$i]||strtolower($input)==$elementpinyin[$i]){
				$elementNumber=$i+1;
				break;
			}
		}
		return isset($elementNumber)?$elementNumber:False;
	}

	public function getelementinfo($input){
		$elementnumber=$this->searchelement($input);
		if($elementnumber){
			require_once('element_xml.php');
			$elementNameArray=getinfo("name");
			$elementAbbrArray=getinfo("abbr");
			$elementIUPACArray=getinfo("iupac");
			$elementMassArray=getinfo("mass");
			$elementOriginArray=getinfo("origin");
			$name = $elementNameArray[$elementnumber-1];
			$Abbr= $elementAbbrArray[$elementnumber-1];
			$IUPACname = $elementIUPACArray[$elementnumber-1];
			$ElementMass=$elementMassArray[$elementnumber-1];
			$ElementOrigin=$elementOriginArray[$elementnumber-1];
			if($elementnumber>0){
				$output="元素名称：".$name."\n元素符号：".$Abbr."\nIUPAC名：".$IUPACname."\n原子序数：".$elementnumber.
				"\n相对原子质量：".$ElementMass."\n元素名称含义：".$ElementOrigin."\n\n<a href='https://en.wikipedia.org/wiki/".$IUPACname."'>访问维基百科</a>";
				return $output;
			}
		}else{
			return False;
		}
	}
	public function getelementtable(){
		require_once('element_xml.php');
		$elementNameArray=getinfo("name");
		$elementAbbrArray=getinfo("abbr");
		$elementIUPACArray=getinfo("iupac");
		$elementMassArray=getinfo("mass");
		$elementOriginArray=getinfo("origin");
		for($elementnumber=1;$elementnumber<=118;$elementnumber++){
			$name = $elementNameArray[$elementnumber-1];
			$Abbr= $elementAbbrArray[$elementnumber-1];
			$IUPACname = $elementIUPACArray[$elementnumber-1];
			$ElementNumber=$elementnumber;
			$ElementMass=$elementMassArray[$elementnumber-1];
			$output=(isset($output)?$output:"").$ElementNumber.".".$name.$Abbr." ".$ElementMass."\n";
		}
		return rtrim($output,"\n")."";
	}
	public function calculatemass($x){
		require_once('element_xml.php');
		$elementNameArray=getinfo("name");
		$elementAbbrArray=getinfo("abbr");
		$elementMassArray=getinfo("mass");
		$l = strlen($x);
		$s = 0;
		$m = 0;
		$massPer=array();
		$y1 = "";
		$y2 = "";
		$y3 = "";
		$y4 = "";
		$T = "";
		$AtomNumber = array();
		for ($i = 0; $i < 118; $i++) $AtomNumber[$i + 1] =0;
		$MulNumber = array();
		$MulIf = array();
		$MulLeft = array();
		$MulRight = array();
		$MulNum = array();
		if ($l > 0) {
			for ($i=1;$i <=$l;$i++) {
				$MulNumber[$i] = 1;
				$y1 = substr($x,$i - 1,1);
				if ($this->calAsc($y1) == 4)
					$MulIf[$i] = 1;
				else if ($this->calAsc($y1) == 5)
					$MulIf[$i] = -1;
				else
					$MulIf[$i] = 0;
				$s = $s + $MulIf[$i];
			}
			if ($s == 0) {
				$n = 0;
				for ($i = 1;$i < $l;$i++) {
					if ($MulIf[$i] == 1) {
						$n++;
						$c = 1;
						$i2 = $i + 1;
						$MulLeft[$n] = $i;
						while ($c > 0) {
							$c = $c + $MulIf[$i2];
							$i2++;
						}
						$i2 = $i2 - 1;
						$MulRight[$n] = $i2;
						if ($i2 + 1 > $l)
							$y3 = "a";
						else
							$y3 = substr($x,$i2, 1);
						if ($this->calAsc($y3) == 3) {
							if ($i2 + 2 > $l)
								$y4 = "a";
							else
								$y4 = substr($x,$i2 + 1, 1);
								if ($this->calAsc($y4) == 3)
									$MulNum[$n] = (int)($y3.$y4);
								else
									$MulNum[$n] = (int)($y3);
						} else {
							$MulNum[$n] = 1;
						}
					}
				}
				for ($i = 1;$i <= $n;$i++) {
					for ($i2 = $MulLeft[$i]; $i2 <= $MulRight[$i]; $i2++)
						$MulNumber[$i2] = $MulNumber[$i2] * $MulNum[$i];
				}
				for ($i = 1;$i <= $l;$i++) {
					$y1 = substr($x,$i - 1, 1);
					if ($this->calAsc($y1) == 1) {
						if ($i >=$l)
							$y2 = "1";
						else
							$y2 = substr($x,$i, 1);
						if ($this->calAsc($y2) == 2) {
							$T = $y1.$y2;
							$n = $this->ElementChoose($T);
							if ($n) {
								if ($i + 1 >=$l)
									$y3 = "1";
								else
									$y3 = substr($x,$i + 1, 1);
								if ($this->calAsc($y3) == 3) {
									if ($i + 2 >=$l)
										$y4 = "a";
									else
										$y4 = substr($x,$i + 2, 1);
									if ($this->calAsc($y4) == 3) {
										$AtomNumber[$n] = $AtomNumber[$n] + (int)($y3.$y4) * $MulNumber[$i];
										$i = $i + 3;
									} else {
										$AtomNumber[$n] = $AtomNumber[$n] + (int)($y3) * $MulNumber[$i];
										$i = $i + 2;
									}
								} else {
									$AtomNumber[$n] = $AtomNumber[$n] + $MulNumber[$i];
									$i++;
								}
							}
						} else if ($this->calAsc($y2) == 3) {
							$n = $this->ElementChoose($y1);
							if ($n) {
								if ($i + 1 >=$l)
									$y3 = "a";
								else
									$y3 = substr($x,$i + 1, 1);
								if ($this->calAsc($y3) == 3) {
									$AtomNumber[$n] = $AtomNumber[$n] + (int)($y2.$y3) * $MulNumber[$i];
									$i = $i + 2;
								} else {
									$AtomNumber[$n] = $AtomNumber[$n] + (int)($y2) * $MulNumber[$i];
								}
							}
						} else {
							$n = $this->ElementChoose($y1);
							if ($n)
								$AtomNumber[$n] = $AtomNumber[$n] + $MulNumber[$i];
						}
					} else if ($this->calAsc($y1) == 4) {
					} else if ($this->calAsc($y1) == 5) {
						if ($i >=$l)
							$y2 = "a";
						else
							$y2 = substr($x,$i, 1);
						if ($this->calAsc($y2) == 3) {
							if ($i + 1 >=$l)
								$y2 = "a";
							else
								$y3 = substr($x,$i + 1, 1);
							if ($this->calAsc($y3) == 3) $i++;
							$i++;
						}
					}
				}
				for ($i = 0; $i < 118; $i++) {
					if($AtomNumber[$i + 1]) {
						$m = $m + $AtomNumber[$i + 1] * (double)($elementMassArray[$i]);
					}
				}
			}
		}
		if ($m > 0) {
			$output=$x."\n相对分子质量=".sprintf("%.2f",$m);
			for($i=0;$i<118;$i++){
				if($AtomNumber[$i+1]>0){
					$massPer[$i+1]=$AtomNumber[$i + 1] * ($elementMassArray[$i])/$m*100;
					$output=$output."\n".$elementNameArray[$i]."（符号：".$elementAbbrArray[$i]."），".$AtomNumber[$i+1].
					"个原子，原子量为".$elementMassArray[$i]."，质量分数为".sprintf("%.2f",$massPer[$i+1])."%；";
				}
			}
			$output=rtrim($output,"；")."。";
			return $output;
		} else {
			return False;
		};
	}
	public function calculategas($mode,$value1,$value2,$value3,$R=8.314){
		switch(strtolower($mode)){
			case "p":
				$V=(double)$value1;
				$n=(double)$value2;
				$T=(double)$value3;
				$p=sprintf("%.3f",$n*$R*$T/$V);
				return "V=".$V."L, n=".$n."mol, T=".$T."K\n计算得p=".$p."kPa";
				break;
			case "v":
				$p=(double)$value1;
				$n=(double)$value2;
				$T=(double)$value3;
				$V=sprintf("%.3f",$n*$R*$T/$p);
				return "p=".$p."kPa, n=".$n."mol, T=".$T."K\n计算得p=".$V."L";
				break;
			case "n":
				$p=(double)$value1;
				$V=(double)$value2;
				$T=(double)$value3;
				$n=sprintf("%.3f",$p*$V/$R/$T);
				return "p=".$p."kPa, V=".$V."L, T=".$T."K\n计算得n=".$n."mol";
				break;
			case "t":
				$p=(double)$value1;
				$V=(double)$value2;
				$n=(double)$value3;
				$T=sprintf("%.3f",$p*$V/$n/$R);
				return "p=".$p."kPa, V=".$V."L, n=".$n."mol\n计算得T=".$T."K";
				break;
			default:
				return False;
		}
	}
	public function calculateacid($type,$c,$strpKaArray,$liquidpKa=-1.74,$pKw=14){
		if(strtoupper($type)=="HA")$AorB=true;else $AorB=false;
		if($AorB){
			$ABname="A";
			$ABnameall="HA";
		}else{
			$ABname="B";
			$ABnameall="BOH";
		}
		$valpKa=array();
		for($i=0;$i<count($strpKaArray);$i++){
			$valpKa[$i]=(double)$strpKaArray[$i];
			if ($valpKa[$i]<$liquidpKa) $valpKa[$i]=$liquidpKa;
		}
		$pH=$this->calpH($valpKa,$c,$pKw);
		$cAB=$this->calpHtoc($valpKa,$c,$pH);
		if(!$AorB) $pH=$pKw-$pH;
		$H=pow(10,-$pH);
		$acidOutput=$ABnameall." ,c=".$c."mol/L, ";
		for($i=0;$i<count($valpKa);$i++){
			if($AorB)$acidOutput=$acidOutput."pKa";else $acidOutput=$acidOutput."pKb";
			if(count($valpKa)>1)$acidOutput=$acidOutput.($i+1);
			$acidOutput=$acidOutput."=".$strpKaArray[$i].", ";
		}
		$acidOutput=$acidOutput."\n溶液的pH为".sprintf("%.2f",$pH).".";
		$acidOutput=$acidOutput."\n"."c(H+)=".sprintf("%1$.2e",$H)."mol/L,";
		for($i=0;$i<count($cAB);$i++){
			$cABoutput="c(";
			if($AorB){
				if($i<count($cAB)-1){
					$cABoutput=$cABoutput."H";
					if(count($cAB)-$i>2) $cABoutput=$cABoutput.(count($cAB) - $i-1);
				}
				$cABoutput=$cABoutput.$ABname;
				if($i>0){
					if($i>1) $cABoutput=$cABoutput.($i);
					$ABoutput=$cABoutput."-";
				}
			}else{
				$cABoutput=$cABoutput.$ABname;
				if(count($cAB)-$i>2){
					$cABoutput=$cABoutput."(OH)".(count($cAB)- $i-1);
				}else if(count($cAB)-$i==2){
					$cABoutput=$cABoutput."OH";
				}
				if($i>0){
					if($i>1) $cABoutput=$cABoutput."".($i);
					$cABoutput=$cABoutput."+";
				}
			}
			$cABoutput=$cABoutput.")=";
			$acidOutput=$acidOutput."\n".$cABoutput.sprintf("%1$.2e",$cAB[$i])."mol/L,";
		}
		$acidOutput=rtrim($acidOutput,",").".";
		return $acidOutput;
	}
	public function calcatedeviation($arr){
		for($i=0;$i<count($arr);$i++){
			$sum=(isset($sum)?$sum:0)+$arr[$i];
			$len=strlen($arr[$i]);
			if(substr($arr[$i],0,1)=="-")$len=$len-1;
			if(strpos($arr[$i],".")){
				$len=$len-1;
				$pointlen=$len-strpos($arr[$i],".");
				if(abs($arr[$i])<1){
					$zeronum=floor(log10(abs($arr[$i])));
					$len=$len+$zeronum;
				}
			}else{
				$pointlen=0;
			}
			if($i>0){
				if($len<$numnum)$numnum=$len;
				if($pointlen<$pointnum)$pointnum=$pointlen;
			}else{
				$numnum=$len;
				$pointnum=$pointlen;
			}
		}
		$average=$sum/count($arr);
		for($i=0;$i<count($arr);$i++){
			$arrabs=abs($arr[$i]-$average);
			$arrsqure=pow($arr[$i]-$average,2);
			$abssum=(isset($abssum)?$abssum:0)+$arrabs;
			$squresum=(isset($squresum)?$squresum:0)+$arrsqure;
		}
		$deviation=$abssum/count($arr);
		$deviation_relatibe=$deviation/$average*1000;
		$s=sqrt($squresum/(count($arr)-1));
		$s_relatibe=$s/$deviation*1000;
		$output="您输入的数据：".join(" ",$arr)."\n平均数：".sprintf("%.".$pointnum."f",$average)."\n平均偏差：".sprintf("%.".$pointnum."f",$deviation)."\n相对平均偏差：".sprintf("%.".($numnum-1)."e",$deviation_relatibe)."‰\n标准偏差：".sprintf("%.".($numnum-1)."e",$s)."\n相对标准偏差：".sprintf("%.".($numnum-1)."e",$s_relatibe)."‰";
		return $output;
	}
	private function calAsc($x) {
		$c = substr($x,0,1);
		$n = ord($c);
		if ($n > 64 & $n < 91)
			return 1;
		else if ($n > 96 & $n < 123)
			return 2;
		else if ($n > 47 & $n < 58)
			return 3;
		else if ($n == 40 | $n == 91 | $n == -23640)
			return 4;
		else if ($n == 41 | $n == 93 | $n == -23639)
			return 5;
		else
			return 0;
	}
	private function calpH($pKa,$c,$pKw) {
		$Ka1=pow(10,-$pKa[0]);
		$Kw=pow(10,-$pKw);
		$cH=(sqrt($Ka1*$Ka1+4*$Ka1*$c+$Kw)-$Ka1)*0.5;
		if($cH>0) return -log10($cH); else return 1024;
	}
	private function calpHtoc($pKa,$c,$pH){
		$D=0;$E=1;
		$G=array();$Ka=array();$pHtoc=array();
		$H=pow(10,-$pH);
		$F=pow($H,count($pKa)+1);
		for($i=0;$i<count($pKa);$i++){
			$Ka[$i]=pow(10,-$pKa[$i]);
		}
		for($i=0;$i<count($pKa)+1;$i++){
			$G[$i]=$F*$E;
			$D=$D+$G[$i];
			$F=$F/$H;
			if($i<count($pKa))$E=$E*$Ka[$i];
		}
		for($i=0;$i<count($pKa)+1;$i++){
			$pHtoc[$i]=$c*$G[$i]/$D;
		}
		return $pHtoc;
	}	
	private function ElementChoose($x) {
		require_once('element_xml.php');
		$elementAbbrArray=getinfo("abbr");	
		for($i=0;$i<118;$i++) {
			if($x==($elementAbbrArray[$i])){
				$elementNumber=$i+1;
				break;
			}
		}
		return isset($elementNumber)?$elementNumber:False;
	}	
}