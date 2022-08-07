<?php

namespace app\models;

use yii\mongodb\ActiveRecord;

/**
 * ContactForm is the model behind the contact form.
 */
class Paper extends ActiveRecord
{
	
	public static function CollectionName()
	{
		return 'papers';
	}

	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	public function attributes()
	{
		return [
			"_id", "created_at", "date", "codbdi", "codneg", "tpmerc", "nomres",
			"especi", "prazot", "modref", "preab", "premax", "premin", "premed", 
			"preult", "preofc", "preofv", "totneg", "quatot", "voltot", "preexe", "indopc", "datven", 
			"fatcot", "ptoexe", "codisi", "dismes", "state", "t_state", "state_avg"
		];
	}

	public static function toIsoDate($timestamp)
	{
		return new \MongoDB\BSON\UTCDateTime($timestamp * 1000);
	}

}
