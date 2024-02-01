/* eslint-disable react/jsx-props-no-spreading */
import {
  Tooltip,
  TooltipProps,
  Typography,
  styled,
  tooltipClasses,
} from '@mui/material';

import { colorTheme } from '../UiColorTheme';

import styles from './styles';

export const ServicesTooltip: React.FC<TooltipProps> = styled(
  ({ className, ...props }: TooltipProps) => {
    const { children } = props;
    return (
      <Tooltip {...props} classes={{ popper: className }}>
        <Typography component="span" sx={styles.hoveredCard}>
          {children}
        </Typography>
      </Tooltip>
    );
  }
)(() => ({
  [`& .${tooltipClasses.tooltip}`]: {
    backgroundColor: '#fff',
    maxWidth: '20.625rem',
    border: `1px solid ${colorTheme.palette.grey400.main}`,
    padding: '1.125rem 1.5rem',
    borderRadius: '0.5rem',
    '@media (max-width: 1439.98px)': {
      display: 'none',
    },
  },
}));

export const FormRulesTooltip: React.FC<TooltipProps> = styled(
  ({ className, ...props }: TooltipProps) => (
    <Tooltip {...props} classes={{ popper: className }}>
      <Typography
        component="div"
        sx={{ display: 'flex', alignItems: 'center', cursor: 'pointer' }}
      >
        {props.children}
      </Typography>
    </Tooltip>
  )
)({
  [`& .${tooltipClasses.tooltip}`]: {
    maxWidth: '11.125rem',
    backgroundColor: '#fff',
    boxShadow: '0px 8px 27px 0px rgba(49, 59, 67, 0.14)',
    padding: '0.625rem 0.75rem',
    borderRadius: '0.5rem',
    '@media (max-width: 1439.98px)': {
      display: 'none',
    },
  },
});
