import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';

import Imagess from '../../../assets/svg/auth-section/bg.svg';
import Images from '../../../assets/svg/auth-section/image.svg';

export default {
  formWrapper: {
    position: 'relative',
    mt: '4.063rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      mt: '3.875rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      mt: '2.125rem',
    },
  },

  backgroundBlock: {
    position: 'absolute',
    borderRadius: '0.75rem 0.75rem 0 0',
    top: '16.2%',
    right: '5%',
    width: '31.25rem',
    maxHeight: ' 33.875rem',
    height: '100dvh',
    backgroundColor: colorTheme.palette.brandGray.main,
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      maxHeight: ' 39.313rem',
      top: '8.3%',
      right: '26%',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      top: '6.8%',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      display: 'none',
    },
  },

  formTitle: {
    paddingBottom: '2rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      maxWidth: '22.313rem',
      paddingBottom: '1.25rem',
    },

    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      maxWidth: '15.313rem',
      fontSize: '1.375rem',
      fontStyle: 'normal',
      fontWeight: '700',
      lineHeight: 'normal',
      paddingBottom: '1.188rem',
    },
  },

  formContent: {
    position: 'relative',
    zIndex: '5',
    padding: '2.25rem 2.5rem 2.5rem 2.5rem',
    borderRadius: '2rem 2rem 0 0',
    border: '1px solid  primary.main',
    background: colorTheme.palette.white.main,
    minHeight: ' 40.438rem',
    maxWidth: '31.375rem',
    boxShadow: '1px 1px 41px 0px rgba(59, 68, 80, 0.05)',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      padding: '2.5rem 2.563rem 3.5rem 2.563rem',
      minWidth: '39.75rem',
      maxHeight: '42.875rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      minHeight: '100%',
      minWidth: '100%',
      maxWidth: '21.563rem',
      maxHeight: '512px',
      padding: '1.5rem 1.5rem 2rem 1.5rem',
    },
  },

  inputsWrapper: {
    flexDirection: 'column',
    gap: '1.375rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      gap: '0.9375rem',
    },
  },

  inputWrapper: {
    flexDirection: 'column',
    gap: '0.563rem',
    position: 'relative',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      gap: '0.313rem',
    },
  },

  inputTitle: {
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      fontSize: '1rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      fontSize: '0.875rem',
    },
  },

  buttonWrapper: {
    maxWidth: '10.938rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      height: '4.375rem',
      maxWidth: '100%',
    },

    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      height: '3.125rem',
      maxWidth: '100%',
    },
  },

  labelText: {
    mt: '1.25rem',
    mb: '2rem',
    mx: '0',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      mb: '1.5rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      mb: '1.188rem',
    },
  },

  tip: {
    lineHeight: '0',
  },

  button: { height: '100%' },
  privacyText: {
    letterSpacing: '0rem',
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      fontSize: '1rem',
      maxWidth: '25.813rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      fontSize: '0.875rem',
    },
  },

  backgroundImage: {
    backgroundImage: `url(${Images.src})`,
    width: '100dvw',
    maxWidth: '49rem',
    height: '41rem',
    position: 'absolute',
    left: '-40%',
    bottom: '0%',
    zIndex: '1',

    [`@media (max-width: ${breakpointsTheme.breakpoints.values.xl}px)`]: {
      backgroundImage: `url(${Imagess.src})`,
      left: '-12%',
      bottom: '16.5%',
      width: '100dvw',
      maxWidth: '50.938rem',
      height: '34.5rem',
    },

    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      display: 'none',
    },
  },
};
