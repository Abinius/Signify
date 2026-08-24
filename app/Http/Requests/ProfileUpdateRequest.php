<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 授权校验由 Policy 在控制器层执行。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 社交链接数组预处理：协议补全 → 去空白 → 去空项 → 去重 → 保序（供下方 max:5 校验）
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('social_links')) {
            $this->merge([
                'social_links' => collect($this->input('social_links'))
                    ->map(fn ($v) => $this->normalizeLink((string) $v))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),
            ]);
        }
    }

    /**
     * 社交链接协议补全：用户输入不带协议时自动补 http://（无需手输前缀）。
     * 已带协议原样保留；协议相对 //xxx 补 http:。
     */
    private function normalizeLink(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        // 已带协议（http://、https://、ftp:// 等）原样保留
        if (preg_match('~^[a-z][a-z0-9+.\-]*://~i', $url)) {
            return $url;
        }
        // 协议相对 //xxx → http://xxx
        if (str_starts_with($url, '//')) {
            return 'http:' . $url;
        }
        return 'http://' . $url;
    }

    /**
     * Get the validation rules that apply to the request.
     * 头像不做 image/mimes 校验（服务器无 fileinfo），改由 AvatarService 扩展名白名单 + GD 校验。
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:100',
            'title' => 'sometimes|string|max:100|nullable',
            'share_slug' => 'sometimes|nullable|string|min:3|max:40|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            'avatar' => ['sometimes', 'file', 'max:2048'],
            'portrait' => ['sometimes', 'file', 'max:2048'],
            'industry' => 'sometimes|string|max:100|nullable',
            'city' => 'sometimes|string|max:100|nullable',
            'bio' => 'sometimes|string|max:500|nullable',
            'contact_phone' => 'sometimes|string|max:20|nullable',
            'contact_email' => 'sometimes|email|max:100|nullable',
            'social_links' => 'nullable|array|max:5',
            'social_links.*' => 'url|max:500',
            'wechat_qrcode' => ['sometimes', 'file', 'max:2048'],
        ];
    }
}
