<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\Helper;

class Menu extends Model
{
	protected $table = 'menu';
    public $timestamps = false;

	public $fillable = [
		'status',
	];

	public function localization(){
		return $this->morphMany('App\Models\Localization', 'localizable');
	}

	public function saveLocalization($request){
		$localization = new Localization();
		$localization->saveLocalization($request, $this, Helper::localizationFields(['name', 'title', 'description']));
	}

	public function localize($language, $field){
        $localization = $this->localization->first(function ($value, $key) use ($language, $field){
            return $value->language == $language && $value->field == $field;
        });
        if(empty($localization))
            $localization = $this->localization()->where(['language' => $language, 'field' => $field])->first();

		if(empty($localization)) {
			return '';
		}else{
			return $localization->value;
		}
	}

	private function getAttributeByName($name){
		$localization = $this->localization->where('language', app()->getLocale())->where('field', $name)->first();
		if(empty($localization)){
			return '';
		}else{
			return $localization->value;
		}
	}

	public function getNameAttribute(){
		return $this->getAttributeByName('name');
	}

	public function getLinksAttribute(){
	    return $this->status ? MenuItem::getMenu($this->id) : null;
    }
}