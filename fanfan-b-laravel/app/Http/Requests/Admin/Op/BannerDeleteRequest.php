<?php

namespace App\Http\Requests\Admin\Op;
use App\Http\Requests\FormRequest;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BannerDeleteRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['banner_id'] = $this->route('id');

    return $request;
  }


  public function rules()
  {
    return [
      'banner_id' => [
        'nullable',
        Rule::exists(Banner::getTableName(), 'id')->whereNull('deleted_at')
      ],
    ];
  }

  public function messages()
  {
    return [
      //
    ];
  }

  protected function prepareForValidation(): void
  {
    $addParamArray = [
      //
    ];

    foreach ($addParamArray as $key => $value) {
      if (!$this->has($key)) {
        $this->merge([
          $key => $value
        ]);
      }
    }
  }
}
