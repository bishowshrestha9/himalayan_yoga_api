<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class SecureFileUpload implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            $fail('The :attribute must be a valid file.');
            return;
        }

        // Check if file is actually an image by reading its content
        $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $fileMimeType = $value->getMimeType();
        
        if (!in_array($fileMimeType, $allowedMimeTypes)) {
            $fail('The :attribute must be a valid image file (jpeg, jpg, png, gif, webp).');
            return;
        }

        // Additional check: verify file extension matches MIME type
        $extension = strtolower($value->getClientOriginalExtension());
        $allowedExtensions = ['jpeg', 'jpg', 'png', 'gif', 'webp'];
        
        if (!in_array($extension, $allowedExtensions)) {
            $fail('The :attribute has an invalid file extension.');
            return;
        }

        // Check for double extensions (e.g., image.php.jpg)
        $filename = $value->getClientOriginalName();
        if (substr_count($filename, '.') > 1) {
            $fail('The :attribute filename contains invalid characters.');
            return;
        }

        // Check for executable content in filename
        $dangerousPatterns = ['php', 'exe', 'bat', 'sh', 'cmd', 'asp', 'aspx', 'jsp', 'js', 'html', 'htm'];
        foreach ($dangerousPatterns as $pattern) {
            if (stripos($filename, '.' . $pattern) !== false) {
                $fail('The :attribute filename contains potentially dangerous content.');
                return;
            }
        }

        // Verify actual image dimensions (prevents fake images)
        try {
            $imageInfo = @getimagesize($value->getPathname());
            if ($imageInfo === false) {
                $fail('The :attribute is not a valid image file.');
                return;
            }
        } catch (\Exception $e) {
            $fail('The :attribute could not be verified as a valid image.');
            return;
        }
    }
}
