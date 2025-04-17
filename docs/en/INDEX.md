# tl;dr

## PHP Error tracking

1. Add your site to sentry.io

2. Set the following variable in you `.env` file and you are done! The URL is provided by sentry.io (the one here is bogus).

```env
SS_SENTRY_DNS="https://2348992804e92837489u@234234234234234234.ingest.sentry.io/234234234234"
```

## Frontend Sentry Integration

For now, add with a script tag at the top of the page as instructed when setting up a javascript Sentry project.

During creation, Sentry will provide a Loader Script. Include this in the `<head>` of your `Page.ss` template.

- Consider getting link from .env and using `TemplateGlobalProvider` to get link
- Lighthouse speed
- Load Async
- Consider loading for only a subset of visits as per below:

```php
class PageController extends ContentController
{

    public function FrontEndSentryCode()
    {
        if(rand(0,9999) === 1) {
            $link = Enviroment::getEnv('SS_SENTRY_DNS_FRONT_END');
            return '
                <script src="'.$link.'" crossorigin="anonymous"></script>
            ';
        }
    }
}
```

in your `Page.ss` template:

```ss
$FrontEndSentryCode
```
