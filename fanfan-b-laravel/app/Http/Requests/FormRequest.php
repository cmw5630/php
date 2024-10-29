<?php

namespace App\Http\Requests;

use App\Enums\ErrorDefine;
use App\Models\Code;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use ReturnData;
use Str;
use Symfony\Component\HttpFoundation\Response;

abstract class FormRequest extends BaseFormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   *
   * @return bool
   */
  public function authorize()
  {
    return true;
  }

  /**
   * If validator fails return the exception in json form
   * @param Validator $validator
   * @return array
   */
  protected function failedValidation(Validator $validator)
  {
    throw new ValidationException($validator, ReturnData::setError([ErrorDefine::VALIDATION_ERROR, $validator->errors()])->send(Response::HTTP_BAD_REQUEST));
  }



  abstract public function rules();

  protected function perPageRule($key = 'api')
  {
    return [
      'integer',
      'min:1',
      'max:'.config('constant.'.Str::upper($key).'_MAX_PER_PAGE')
    ];
  }

  protected function codeExists($category)
  {
    return Rule::exists(Code::getTableName(), 'code')->where('category', $category)
      ->whereNull('deleted_at')->whereNotNull('code');
  }
}
