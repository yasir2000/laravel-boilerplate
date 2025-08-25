<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media Library Configuration
    |--------------------------------------------------------------------------
    */

    'disk_name' => env('MEDIA_DISK', 'media'),

    'max_file_size' => 1024 * 1024 * 10, // 10MB

    'queue_connection_name' => env('QUEUE_CONNECTION', 'sync'),

    'queue_name' => '',

    'queue_conversions_by_default' => env('QUEUE_CONVERSIONS_BY_DEFAULT', true),

    'media_model' => Spatie\MediaLibrary\MediaCollections\Models\Media::class,

    'remote' => [
        'extra_headers' => [
            'CacheControl' => 'max-age=604800',
        ],
    ],

    'responsive_images' => [
        'width_calculator' => Spatie\MediaLibrary\ResponsiveImages\WidthCalculator\FileSizeOptimizedWidthCalculator::class,
        'use_tiny_placeholders' => true,
        'tiny_placeholder_generator' => Spatie\MediaLibrary\ResponsiveImages\TinyPlaceholderGenerator\Blurred::class,
    ],

    'enable_vapor_uploads' => env('ENABLE_MEDIA_LIBRARY_VAPOR_UPLOADS', false),

    'generate_thumbnails_for_temporary_uploads' => true,

    'file_namer' => Spatie\MediaLibrary\Support\FileNamer\DefaultFileNamer::class,

    'path_generator' => Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator::class,

    'url_generator' => Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator::class,

    'moves_media_on_update' => false,

    'version_urls' => false,

    'image_optimizers' => [
        Spatie\ImageOptimizer\Optimizers\Jpegoptim::class => [
            '-m85',
            '--force',
            '--strip-all',
            '--all-progressive',
        ],

        Spatie\ImageOptimizer\Optimizers\Pngquant::class => [
            '--force',
        ],

        Spatie\ImageOptimizer\Optimizers\Optipng::class => [
            '-i0',
            '-o2',
            '-quiet',
        ],

        Spatie\ImageOptimizer\Optimizers\Svgo::class => [
            '--disable=cleanupIDs',
        ],

        Spatie\ImageOptimizer\Optimizers\Gifsicle::class => [
            '-b',
            '-O3',
        ],

        Spatie\ImageOptimizer\Optimizers\Cwebp::class => [
            '-m', '6',
            '-pass', '10',
            '-mt',
            '-q', '90',
        ],

        Spatie\ImageOptimizer\Optimizers\Avifenc::class => [
            '-a', 'cq-level=23',
            '-j', '8',
            '--min', '0',
            '--max', '63',
            '--minalpha', '0',
            '--maxalpha', '63',
            '-a', 'end-usage=q',
            '-a', 'tune=ssim',
        ],
    ],

    'image_generators' => [
        Spatie\MediaLibrary\Conversions\ImageGenerators\Image::class,
        Spatie\MediaLibrary\Conversions\ImageGenerators\Webp::class,
        Spatie\MediaLibrary\Conversions\ImageGenerators\Avif::class,
        Spatie\MediaLibrary\Conversions\ImageGenerators\Pdf::class,
        Spatie\MediaLibrary\Conversions\ImageGenerators\Svg::class,
        Spatie\MediaLibrary\Conversions\ImageGenerators\Video::class,
    ],

    'temporary_upload_path_generator' => null,

    'image_driver' => env('IMAGE_DRIVER', 'gd'),

];
