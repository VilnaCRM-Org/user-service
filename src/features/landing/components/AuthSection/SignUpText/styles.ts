import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';

export default {
  title: {
    paddingTop: '4rem',
    marginTop: '-4rem',
    paddingBottom: '2.5rem',
    [`@media (max-width: 1130px)`]: {
      textAlign: 'center',
      maxWidth: '42.688rem',
      paddingBottom: '2rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      fontSize: '1.75rem',
      textAlign: 'left',
      paddingBottom: '1.25rem',
    },
  },
  titleVilnaCRM: {
    color: colorTheme.palette.primary.main,
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      fontSize: '1.75rem',
      textAlign: 'left',
    },
  },
  textWrapper: {
    pt: '8.5rem',
    width: '50%',
    maxWidth: '35.063rem',
    [`@media (max-width: 1130px)`]: {
      maxWidth: '100%',
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      width: '100%',
      pt: '3.5rem',

      [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
        pt: '1.625rem',
      },
    },
  },

  signInText: {
    mb: '1.5rem',
    [`@media (max-width: 1130px)`]: {
      textAlign: 'center',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.sm}px)`]: {
      fontSize: '1.125rem',
      alignSelf: 'start',
      mb: '1.375rem',
    },
  },
};
