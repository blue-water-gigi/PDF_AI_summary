<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Mappers\SubscriptionViewMapper;
use App\Models\User;
use App\Notifications\SummaryCreated as SummaryCreatedNotification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Override;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    #[Override]
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim((string) $message), 'author' => trim((string) $author)],
            'auth' => [
                'user' => $user,
            ],
            'subscriptionData' => fn (): ?array => $user instanceof User
                ? app(SubscriptionViewMapper::class)->sharedProps($user)
                : null,
            'notifications' => fn (): array => $user instanceof User
                ? $this->notificationProps($user)
                : $this->emptyNotificationProps(),
        ];
    }

    /**
     * @return array{unreadCount: int, unreadSummaryCount: int, unreadSummaryItems: array<int, array{id: string, summaryId: int}>}
     */
    private function notificationProps(User $user): array
    {
        $summaryNotifications = $user->unreadNotifications()
            ->where('type', SummaryCreatedNotification::class)
            ->get(['id', 'data'])
            ->map(fn ($notification): array => [
                'id' => (string) $notification->id,
                'summaryId' => (int) data_get($notification->data, 'summary_id', 0),
            ])
            ->filter(fn (array $notification): bool => $notification['summaryId'] > 0)
            ->values()
            ->all();

        return [
            'unreadCount' => $user->unreadNotifications()->count(),
            'unreadSummaryCount' => $user->unreadNotifications()
                ->where('type', SummaryCreatedNotification::class)
                ->count(),
            'unreadSummaryItems' => $summaryNotifications,
        ];
    }

    /**
     * @return array{unreadCount: int, unreadSummaryCount: int, unreadSummaryItems: array<int, array{id: string, summaryId: int}>}
     */
    private function emptyNotificationProps(): array
    {
        return [
            'unreadCount' => 0,
            'unreadSummaryCount' => 0,
            'unreadSummaryItems' => [],
        ];
    }
}