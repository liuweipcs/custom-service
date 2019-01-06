<?php
namespace app\modules\services\modules\cdiscount\components;
class cdXmlHelper{
	//操作xml文档，存入一个数组，自动变成xml文件
	private $_xmlOpenPrefix = '<';
	private $_xmlClosePrefix = '>';
	private $_xmlOpenEndPrefix = '</';
	private $_xmlAutoClosePrefix = '/>';
	
	private $_globalPrefix = '';
	
	/**
	 * XmlUtils constructor.
	 * @param $prefix
	 */
	public function __construct($prefix)
	{
		$this->_globalPrefix = $prefix;
	}
	
	/**
	 * @param $prefix
	 */
	public function setGlobalPrefix($prefix)
	{
		$this->_globalPrefix = $prefix;
	}
	
	/**
	 * @param $tag
	 * @param $value
	 * @return string
	 */
	public function generateBalise($tag, $value)
	{
		$balise = $this->_xmlOpenPrefix . $this->_globalPrefix . $tag . $this->_xmlClosePrefix .
		$value .
		$this->_xmlOpenEndPrefix . $this->_globalPrefix . $tag . $this->_xmlClosePrefix;
		return $balise;
	}
	
	/**
	 * @param $tag
	 * @return string
	 */
	public function generateOpenBalise($tag)
	{
		return $this->_xmlOpenPrefix . $this->_globalPrefix . $tag . $this->_xmlClosePrefix;
	}
	
	/**
	 * @param $tag
	 * @return string
	 */
	public function generateCloseBalise($tag)
	{
		return $this->_xmlOpenEndPrefix . $this->_globalPrefix . $tag . $this->_xmlClosePrefix;
	}
	
	/**
	 * @param $tag
	 * @param $inlineTAG
	 * @param $value
	 * @return string
	 */
	public function generateAutoClosingBalise($tag, $inlineTAG, $value)
	{
		return $this->_xmlOpenPrefix . $this->_globalPrefix . $tag . ' ' . $inlineTAG . '="' . $value . '" ' . $this->_xmlAutoClosePrefix;
	}
	
	/**
	 * @param $tag
	 * @param $inlines
	 * @return string
	 */
	public function generateOpenBaliseWithInline($tag, $inlines)
	{
		$balise = $this->_xmlOpenPrefix . $this->_globalPrefix . $tag . ' ';
		foreach ($inlines as $inline) {
			$balise .= $inline . ' ';
		}
		return $balise . $this->_xmlClosePrefix;
	}
	
	/**
	 * <cdis:OrderNumberList i:nil="true"/>
	 *
	 * @param $tag
	 * @param $inlineTAG
	 * @param $value
	 * @return string
	 */
	public function generateAutoClosingBaliseWithInline($tag, $inlineTAG, $value)
	{
		return $this->_xmlOpenPrefix . $this->_globalPrefix . $tag . ' ' . $inlineTAG . '="' . $value . '" ' . $this->_xmlAutoClosePrefix;
	}
	
	public function generateAutoClosingBaliseWithInlines($tag,$inlineTAGs){
		$content = '';
		if(!empty($inlineTAGs)){
			foreach ($inlineTAGs as $val){
				$content .= ' '.$val;
			}
		}
		return $this->_xmlOpenPrefix . $this->_globalPrefix . $tag . ' '.$content.' '.$this->_xmlAutoClosePrefix;
	}
	
	//读取xml文本的内容
	public function xmlToArray($xml){
		$dom = new DOMDocument();
		$dom->loadXML($xml);
		return $this->getArray($dom->documentElement);
	}
	
	private function getArray($node) {
		$array = false;
		if($this->hasChildNode($node)){
			foreach ($node->childNodes as $childNode) {
				if ($childNode->nodeType != XML_TEXT_NODE) {
					if($this->hasSameTag($node, $childNode->nodeName)){
						$array[$childNode->nodeName][] = $this->getArray($childNode);
					}else{
						$array[$childNode->nodeName] = $this->getArray($childNode);
					}
				}
			}
		}else{
			return $node->nodeValue;
		}
		return $array;
	}
	
	private function hasChildNode($node){
		if($node->hasChildNodes()){
			foreach ($node->childNodes as $c){
				if($c->nodeType == XML_ELEMENT_NODE)
					return true;
			}
		}
		return false;
	}
	
	private function hasSameTag($node,$tag){
		return $node->getElementsByTagName($tag)->length>1?true:false;
	}
	
}