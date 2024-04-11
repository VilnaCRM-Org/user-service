import breakpointsTheme from '@/components/UiBreakpoints';

import PhoneMainImage from '../../../assets/img/about-vilna/mobile.jpg';
import TabletMainImage from '../../../assets/img/about-vilna/tablet.jpg';

export default {
  mainImageWrapper: {
    overflow: 'hidden',
    borderTopRightRadius: '0.625rem',
    borderTopLeftRadius: '0.625rem',
    marginTop: '-1.125rem',
    height: '31.125rem',
    width: '47.875rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      borderRadius: '0.625rem',
      width: '34.125rem',
      height: '40.813rem',
      marginTop: '0',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      width: '12.75rem',
      height: '27.25rem',
      marginTop: '-1.125rem',
    },

    img: {
      width: '47.875rem',
      height: '31.125rem',
      [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
        backgroundSize: 'cover',
        backgroundRepeat: 'no-repeat',
        width: '100%',
        height: '100%',
      },
      [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
        content: `url(${TabletMainImage.src})`,
      },
      [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
        content: `url(${PhoneMainImage.src})`,
        backgroundSize: 'cover',
        backgroundRepeat: 'no-repeat',
        width: '12.688rem',
        height: '19.75rem',
        borderTopRightRadius: '1.625rem',
        borderTopLeftRadius: '1.625rem',
        borderBottomLeftRadius: '0',
        borderBottomRightRadius: '0',
      },
    },
  },
};
