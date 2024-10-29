<?php

namespace App\Http\Requests\Admin\Op;
use App\Http\Requests\FormRequest;
use App\Models\BlockedIp;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlockIpRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['blocked_ip_id'] = $this->route('id');

    return $request;
  }

  public function rules()
  {
    if ($this->method() === 'DELETE') {
      return [
        'blocked_ip_id' => [
          'required',
          'integer',
          Rule::exists(BlockedIp::getTableName(), 'id')->whereNull('deleted_at'),
        ],
      ];
    }

    return [
      'ip_address' => [
        'required',
        Rule::unique(BlockedIp::getTableName(), 'ip_address')->whereNull('deleted_at'),
        'ip',
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
