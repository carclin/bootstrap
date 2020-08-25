<?php namespace CarClin\Common;

use Valitron\Validator;

class Controller
{
	protected function validate(array $body, array $rules): void
	{
		$validator = new Validator($body);
		$validator->mapFieldsRules($rules);
		if (!$validator->validate()) {
			$errors = $validator->errors();

			if (count($errors) > 0) {
				$message = [];
				foreach ($errors as $errorsGroup) {
					$message[] = implode(', ', $errorsGroup);
				}

				throw new \Exception(implode(', ', $message), 400);
			} else {
				throw new \Exception('Invalid request', 400);
			}
		}
	}
}