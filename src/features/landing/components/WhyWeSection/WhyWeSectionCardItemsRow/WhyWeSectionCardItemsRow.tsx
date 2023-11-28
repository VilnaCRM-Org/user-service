import { Grid } from '@mui/material';
import React from 'react';

import CustomTooltip from '@/components/ui/CustomTooltip/CustomTooltip';
import { IWhyWeCardItem } from '@/features/landing/types/why-we/types';

import WhyWeSectionCardItem from '../WhyWeSectionCardItem/WhyWeSectionCardItem';

interface IWhyWeSectionCardItemsRowProps {
  cardItems: IWhyWeCardItem[];
  style?: React.CSSProperties;
  tooltipIcons: string[];
}

export default function WhyWeSectionCardItemsRow({
  cardItems,
  style,
  tooltipIcons,
}: IWhyWeSectionCardItemsRowProps) {
  return (
    <Grid
      container
      justifyContent="center"
      columnSpacing={1.625}
      sx={{
        ...style,
      }}
    >
      {cardItems.map((cardItem) => (
        <Grid item key={cardItem.id} md={6} lg={4}>
          <CustomTooltip
            title="Regular services"
            text="Integrate in a few clicks"
            icons={tooltipIcons}
          >
            <WhyWeSectionCardItem cardItem={cardItem} />
          </CustomTooltip>
        </Grid>
      ))}
    </Grid>
  );
}

WhyWeSectionCardItemsRow.defaultProps = {
  style: {},
};
