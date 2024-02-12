import LargeCardItem from './LargeCardItem';
import SmallCardItem from './SmallCardItem';
import { CardItemProps } from './types';

function UiCardItem({
  item,
  type,
  imageList,
}: CardItemProps): React.ReactElement {
  return (
    <>
      {type === 'small' && (
        <SmallCardItem item={item} imageList={imageList || []} />
      )}
      {type === 'large' && <LargeCardItem item={item} />}
    </>
  );
}

export default UiCardItem;
