import breakpointsTheme from '@/components/UiBreakpoints';
import colorTheme from '@/components/UiColorTheme';
import { golos } from '@/config/Fonts/golos';

export default {
  apiLinksWrapper: {
    flexDirection: 'row',
    gap: '0.75rem',
    alignItems: 'center',
    backgroundColor: 'white',
    borderRadius: '0.5rem',
    padding: '1.563rem 2.063rem',
    boxShadow: `0px 8px 27px 0px #313B4324`,
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      gap: '1.5rem',
    },
    [`@media (max-width: 61.25rem)`]: {
      gap: '0.75rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      flexDirection: 'column',
      alignItems: 'normal',
      padding: '0.5rem',
      gap: '0.5rem',
    },
  },
  url: {
    color: colorTheme.palette.primary.main,
    textDecoration: 'none',
    cursor: 'pointer',
    fontWeight: '500',
    fontSize: '0.9375rem',
    fontFamily: golos.style.fontFamily,
    [`@media (max-width: 1130px)`]: {
      fontSize: '0.9375rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.lg}px)`]: {
      fontWeight: 600,
      fontSize: '1.125rem',
    },
    [`@media (max-width: 61.25rem)`]: {
      fontSize: '0.9rem',
    },
    [`@media (max-width: ${breakpointsTheme.breakpoints.values.md}px)`]: {
      color: colorTheme.palette.darkSecondary.main,
      fontWeight: 500,
      fontSize: '0.938rem',
      padding: '15px 24px',
      backgroundColor: colorTheme.palette.backgroundGrey300.main,
      borderRadius: '0.5rem',
    },
  },
};
