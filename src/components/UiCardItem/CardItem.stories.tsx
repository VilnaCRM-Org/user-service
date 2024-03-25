import type { Meta, StoryObj } from '@storybook/react';

import UiCardList from '../UiCardList';
import { CardList } from '../UiCardList/types';

import { LARGE_CARD_ITEM, SMALL_CARD_ITEM } from './constants';

const meta: Meta<typeof UiCardList> = {
  title: 'UiComponents/UiCardItem',
  component: UiCardList,
  tags: ['autodocs'],
};

export default meta;

function CardItem(args: CardList): React.ReactElement {
  return <UiCardList {...args} />;
}

type Story = StoryObj<typeof CardItem>;

export const CardItemLarge: Story = {
  args: {
    cardList: [LARGE_CARD_ITEM],
  },
};
export const CardItemSmall: Story = {
  args: {
    cardList: [SMALL_CARD_ITEM],
  },
};
