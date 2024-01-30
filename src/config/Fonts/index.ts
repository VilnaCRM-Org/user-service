import Golos from 'next/font/local';
import Inter from 'next/font/local';

export const golos = Golos({
  src: [
    {
      path: '../../features/landing/assets/fonts/Golos/GolosText-Regular.ttf',
      weight: '400',
      style: 'normal',
    },
    {
      path: '../../features/landing/assets/fonts/Golos/GolosText-Medium.ttf',
      weight: '500',
      style: 'normal',
    },
    {
      path: '../../features/landing/assets/fonts/Golos/GolosText-SemiBold.ttf',
      weight: '600',
      style: 'normal',
    },
    {
      path: '../../features/landing/assets/fonts/Golos/GolosText-Bold.ttf',
      weight: '700',
      style: 'normal',
    },
    {
      path: '../../features/landing/assets/fonts/Golos/GolosText-ExtraBold.ttf',
      weight: '800',
      style: 'normal',
    },
    {
      path: '../../features/landing/assets/fonts/Golos/GolosText-Black.ttf',
      weight: '900',
      style: 'normal',
    },
  ],
});

export const inter = Inter({
  src: [
    {
      path: '../../features/landing/assets/fonts/Inter/Inter-Regular.ttf',
      weight: '400',
      style: 'normal',
    },
    {
      path: '../../features/landing/assets/fonts/Inter/Inter-Medium.ttf',
      weight: '500',
      style: 'normal',
    },
    {
      path: '../../features/landing/assets/fonts/Inter/Inter-Bold.ttf',
      weight: '700',
      style: 'normal',
    },
  ],
});
