<?php
	/*
		insertSet 클래스
	*/
class insertSet {
	
	private $multiQueryFl;					// 멀티쿼리 기능 생성
	private $multiMaxCount;					// 한번에 생성할 멀티 쿼리 수
	private $tableName;						// 테이블명

	private $arrayString;
	private $arrayMultiValue;
	private $arrayInsertQuery;
	public $arrayFieldName;
	
	public function __construct($tableName, $multiQueryFl, $maxCount = 500) {
		$this->multiQueryFl				= $multiQueryFl;
		$this->multiMaxCount			= $maxCount;

		$this->tableName				= $tableName;
		$this->arrayFieldName			= array();
		$this->arrayMultiValue			= array();
		$this->arrayInsertQuery			= array();
	}

	public function querySet($arrayDataValue, $roopCount) {
		$this->arrayString = array();
		if ($this->multiQueryFl == 'n') {
			foreach ($arrayDataValue as $fieldName => $dataValue) {
				if ((($fieldName == 'writerPw' || $fieldName == 'memPw') && (substr($arrayDataValue[$fieldName], 0, 10) == "password('" || substr($arrayDataValue[$fieldName], 0, 5) == "md5('" || substr($arrayDataValue[$fieldName], 0, 6) == "sha2('")) || $arrayDataValue[$fieldName] == 'now()' || $arrayDataValue[$fieldName] == 'NULL') {
					$this->arrayString[] = "$fieldName = " . $dataValue;
				}
				else {
					$this->arrayString[] = "$fieldName = '" . addslashes($dataValue) . "'";
				}
			}
			$this->arrayInsertQuery[] = "Insert Into " . $this->tableName . " Set " . implode(', ', $this->arrayString) . ';';
		}
		else {
			$fieldCount = 0;
			foreach ($this->arrayFieldName as $fieldName) {
				if ((($fieldName == 'writerPw' || $fieldName == 'memPw') && (substr($arrayDataValue[$fieldName], 0, 10) == "password('" || substr($arrayDataValue[$fieldName], 0, 5) == "md5('" || substr($arrayDataValue[$fieldName], 0, 6) == "sha2('")) || $arrayDataValue[$fieldName] == 'now()' || $arrayDataValue[$fieldName] == 'NULL') {
					$this->arrayString[$fieldCount] = $arrayDataValue[$fieldName];
				}
				else {
					$this->arrayString[$fieldCount] = "'" . addslashes($arrayDataValue[$fieldName]) . "'";
				}
			
				$fieldCount++;
			}

			$this->arrayMultiValue[] = '(' . implode(',', $this->arrayString) . ')';
			
			if (($roopCount % $this->multiMaxCount) == 0) {
				$this->multiQuerySet();
			}
		}
	}

	private function multiQuerySet() {
		$this->arrayInsertQuery[] = "Insert Into " . $this->tableName . " (" . implode(', ', $this->arrayFieldName) . ") VALUES " . implode(',', $this->arrayMultiValue) . ';';

		unset($this->arrayMultiValue);
		$this->arrayMultiValue = array();
	}

	public function getQuery ($arrayDataQuery) {
		$arrayReturnQuery = array();
	
		if ($this->multiQueryFl == 'y' && !empty($this->arrayMultiValue)) {
			$this->multiQuerySet();
		}
		
		if (!empty($arrayDataQuery)) {
			$arrayReturnQuery = array_merge($arrayDataQuery, $this->arrayInsertQuery);
		}
		else {
			$arrayReturnQuery = $this->arrayInsertQuery;
		}
		unset($this->arrayInsertQuery);
		$this->arrayInsertQuery = array();

		return $arrayReturnQuery;
	}
}

?>