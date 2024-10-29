<?php

namespace App\Http\Requests\Admin\Op;
use App\Http\Requests\FormRequest;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerListRequest extends FormRequest
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
      'banner_id' => ['nullable', 'exists:' . Banner::getTableName() . ',id'],
      'page' => ['integer', 'min:1'],
      'per_page' => $this->perPageRule()
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
      'banner_id' => null,
      'platform' => null,
      'location' => null,
      'page' => 1,
      'per_page' => 20,
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
