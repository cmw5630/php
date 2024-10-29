<?php

namespace App\Http\Requests;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerUpdateRequest extends FormRequest
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
      'image' => ['required', 'max:3072'],
      'link_url' => ['nullable', 'url'],
      'banner_id' => ['nullable', 'exists:' . Banner::getTableName() . ',id'],
      'order' => ['nullable', 'integer'],
      'started_at' => ['required', 'date'],
      'ended_at' => ['required', 'date', 'after:started_at'],
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
      'order' => null,
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
